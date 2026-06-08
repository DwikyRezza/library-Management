<?php

namespace Tests\Feature\LibraFlow;

use App\Models\Member;
use App\Models\MemberCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Mockery;
use Tests\TestCase;

class GoogleAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_redirects_to_google(): void
    {
        $provider = Mockery::mock(\Laravel\Socialite\Two\GoogleProvider::class);
        $provider->shouldReceive('redirect')
            ->once()
            ->andReturn(redirect('https://accounts.google.com/o/oauth2/auth'));

        Socialite::shouldReceive('driver')
            ->once()
            ->with('google')
            ->andReturn($provider);

        $response = $this->get('/auth/google/redirect/member');

        $response->assertRedirect('https://accounts.google.com/o/oauth2/auth');
        $this->assertSame('member', session('socialite_type'));
    }

    public function test_rejects_invalid_redirect_type(): void
    {
        $response = $this->get('/auth/google/redirect/invalid-type');
        $response->assertStatus(404);
    }

    public function test_member_google_registration_creates_and_authenticates_member(): void
    {
        // Setup default category
        $category = MemberCategory::factory()->create(['name' => 'Regular Student']);

        // Mock Socialite Google User
        $googleUser = Mockery::mock(\Laravel\Socialite\Two\User::class);
        $googleUser->shouldReceive('getId')->andReturn('9876543210');
        $googleUser->shouldReceive('getName')->andReturn('Jane Doe');
        $googleUser->shouldReceive('getEmail')->andReturn('janedoe@gmail.com');
        $googleUser->shouldReceive('getRaw')->andReturn([
            'given_name' => 'Jane',
            'family_name' => 'Doe',
        ]);

        $provider = Mockery::mock(\Laravel\Socialite\Two\GoogleProvider::class);
        $provider->shouldReceive('user')->once()->andReturn($googleUser);

        Socialite::shouldReceive('driver')
            ->once()
            ->with('google')
            ->andReturn($provider);

        // Access callback with type 'member' in session
        $response = $this->withSession(['socialite_type' => 'member'])
            ->get('/auth/google/callback');

        $response->assertRedirect(route('books.search'));
        $this->assertTrue(Auth::guard('member')->check());

        $member = Auth::guard('member')->user();
        $this->assertSame('janedoe@gmail.com', $member->email);
        $this->assertSame('Jane', $member->first_name);
        $this->assertSame('Doe', $member->last_name);
        $this->assertSame('9876543210', $member->google_id);
        $this->assertStringStartsWith('GGL-', $member->roll_number);
        $this->assertSame($category->id, $member->member_category_id);
        $this->assertFalse($member->approved);
        $this->assertFalse($member->rejected);
    }

    public function test_member_google_login_authenticates_existing_member(): void
    {
        $category = MemberCategory::factory()->create();
        $member = Member::factory()->create([
            'email' => 'existing@gmail.com',
            'google_id' => '123456',
            'member_category_id' => $category->id,
            'approved' => true,
        ]);

        $googleUser = Mockery::mock(\Laravel\Socialite\Two\User::class);
        $googleUser->shouldReceive('getId')->andReturn('123456');
        $googleUser->shouldReceive('getEmail')->andReturn('existing@gmail.com');

        $provider = Mockery::mock(\Laravel\Socialite\Two\GoogleProvider::class);
        $provider->shouldReceive('user')->once()->andReturn($googleUser);

        Socialite::shouldReceive('driver')
            ->once()
            ->with('google')
            ->andReturn($provider);

        $response = $this->withSession(['socialite_type' => 'member'])
            ->get('/auth/google/callback');

        $response->assertRedirect(route('books.search'));
        $this->assertTrue(Auth::guard('member')->check());
        $this->assertSame($member->id, Auth::guard('member')->id());
    }

    public function test_member_google_login_binds_by_email_if_google_id_missing(): void
    {
        $category = MemberCategory::factory()->create();
        $member = Member::factory()->create([
            'email' => 'bind@gmail.com',
            'google_id' => null,
            'member_category_id' => $category->id,
        ]);

        $googleUser = Mockery::mock(\Laravel\Socialite\Two\User::class);
        $googleUser->shouldReceive('getId')->andReturn('777888');
        $googleUser->shouldReceive('getEmail')->andReturn('bind@gmail.com');

        $provider = Mockery::mock(\Laravel\Socialite\Two\GoogleProvider::class);
        $provider->shouldReceive('user')->once()->andReturn($googleUser);

        Socialite::shouldReceive('driver')
            ->once()
            ->with('google')
            ->andReturn($provider);

        $response = $this->withSession(['socialite_type' => 'member'])
            ->get('/auth/google/callback');

        $response->assertRedirect(route('books.search'));
        $this->assertTrue(Auth::guard('member')->check());
        $this->assertSame($member->id, Auth::guard('member')->id());
        $this->assertSame('777888', $member->fresh()->google_id);
    }

    public function test_rejected_member_cannot_login_via_google(): void
    {
        $category = MemberCategory::factory()->create();
        Member::factory()->create([
            'email' => 'rejected@gmail.com',
            'google_id' => 'rejected-id',
            'member_category_id' => $category->id,
            'approved' => false,
            'rejected' => true,
        ]);

        $googleUser = Mockery::mock(\Laravel\Socialite\Two\User::class);
        $googleUser->shouldReceive('getId')->andReturn('rejected-id');
        $googleUser->shouldReceive('getEmail')->andReturn('rejected@gmail.com');

        $provider = Mockery::mock(\Laravel\Socialite\Two\GoogleProvider::class);
        $provider->shouldReceive('user')->once()->andReturn($googleUser);

        Socialite::shouldReceive('driver')
            ->once()
            ->with('google')
            ->andReturn($provider);

        $response = $this->withSession(['socialite_type' => 'member'])
            ->get('/auth/google/callback');

        $response->assertRedirect(route('member.login'));
        $this->assertFalse(Auth::guard('member')->check());
        $response->assertSessionHasErrors('login');
    }

    public function test_staff_google_login_authenticates_existing_user(): void
    {
        $user = User::factory()->create([
            'email' => 'staff@libraflow.test',
            'google_id' => null,
            'is_active' => true,
        ]);

        $googleUser = Mockery::mock(\Laravel\Socialite\Two\User::class);
        $googleUser->shouldReceive('getId')->andReturn('staff-google-id');
        $googleUser->shouldReceive('getEmail')->andReturn('staff@libraflow.test');

        $provider = Mockery::mock(\Laravel\Socialite\Two\GoogleProvider::class);
        $provider->shouldReceive('user')->once()->andReturn($googleUser);

        Socialite::shouldReceive('driver')
            ->once()
            ->with('google')
            ->andReturn($provider);

        $response = $this->withSession(['socialite_type' => 'staff'])
            ->get('/auth/google/callback');

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertTrue(Auth::check());
        $this->assertSame($user->id, Auth::id());
        $this->assertSame('staff-google-id', $user->fresh()->google_id);
    }

    public function test_staff_google_login_rejects_unregistered_email(): void
    {
        $googleUser = Mockery::mock(\Laravel\Socialite\Two\User::class);
        $googleUser->shouldReceive('getId')->andReturn('unregistered-google-id');
        $googleUser->shouldReceive('getEmail')->andReturn('unregistered@libraflow.test');

        $provider = Mockery::mock(\Laravel\Socialite\Two\GoogleProvider::class);
        $provider->shouldReceive('user')->once()->andReturn($googleUser);

        Socialite::shouldReceive('driver')
            ->once()
            ->with('google')
            ->andReturn($provider);

        $response = $this->withSession(['socialite_type' => 'staff'])
            ->get('/auth/google/callback');

        $response->assertRedirect(route('login'));
        $this->assertFalse(Auth::check());
        $response->assertSessionHasErrors('login');
    }
}

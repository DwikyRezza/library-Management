<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\BookCategory;
use App\Models\BookCopy;
use App\Models\BorrowTransaction;
use App\Models\Branch;
use App\Models\Member;
use App\Models\MemberCategory;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            $admin = User::query()->create([
                'name' => 'LibraFlow Administrator',
                'username' => 'admin',
                'email' => 'admin@libraflow.test',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'role' => User::ROLE_ADMIN,
                'is_active' => true,
            ]);

            $librarian = User::query()->create([
                'name' => 'Ayu Librarian',
                'username' => 'ayu',
                'email' => 'ayu@libraflow.test',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'role' => User::ROLE_LIBRARIAN,
                'is_active' => true,
            ]);

            $this->call(BookCategorySeeder::class);

            $categories = BookCategory::query()
                ->whereIn('name', array_column(BookCategorySeeder::CATEGORIES, 'name'))
                ->get()
                ->keyBy('name');

            $memberCategories = collect([
                ['Regular Student', 3, 14, 'Default undergraduate borrowing quota.'],
                ['Scholar Student', 5, 14, 'Higher quota for scholarship students.'],
                ['Post Graduate', 6, 14, 'Graduate student borrowing quota.'],
                ['Research Scholar', 8, 14, 'Research-oriented borrowing quota.'],
            ])->mapWithKeys(fn (array $category) => [
                $category[0] => MemberCategory::query()->create([
                    'name' => $category[0],
                    'max_books' => $category[1],
                    'loan_days' => $category[2],
                    'description' => $category[3],
                ]),
            ]);

            $this->call(BranchSeeder::class);

            $branches = Branch::query()
                ->whereIn('code', array_column(BranchSeeder::BRANCHES, 'code'))
                ->get()
                ->keyBy('name');

            $bookSeeds = [
                ['Clean Architecture for Campus Apps', 'Rina Prasetyo', 'Technology'],
                ['Laravel Patterns in Practice', 'Bima Hartono', 'Technology'],
                ['Database Design Field Guide', 'Sari Wijaya', 'Technology'],
                ['Human Computer Interaction Notes', 'Lukas Gunawan', 'Technology'],
                ['Modern Network Fundamentals', 'Dewi Anggraeni', 'Technology'],
                ['Physics of Everyday Systems', 'Maya Rahardjo', 'Science'],
                ['Applied Biology Primer', 'Hasan Malik', 'Science'],
                ['Chemistry Lab Companion', 'Nadia Putri', 'Science'],
                ['Research Methods Handbook', 'Tara Mahendra', 'Science'],
                ['Astronomy for Curious Minds', 'Fajar Nugraha', 'Science'],
                ['The Library at Dawn', 'Mira Santoso', 'Fiction'],
                ['Letters from the Archive', 'Alden Surya', 'Fiction'],
                ['Rain Over Old Campus', 'Nina Larasati', 'Fiction'],
                ['Silent Shelves', 'Damar Wicaksono', 'Fiction'],
                ['North Hall Stories', 'Ratih Ananda', 'Fiction'],
                ['Startup Finance Basics', 'Kevin Tan', 'Business'],
                ['Operational Excellence', 'Mei Tanaka', 'Business'],
                ['Ethical Leadership', 'Jonathan Lee', 'Business'],
                ['Ancient Civilizations Survey', 'Aisha Rahman', 'History'],
                ['Indonesia Through Time', 'Bagus Pratama', 'History'],
            ];

            $books = collect($bookSeeds)->map(function (array $seed, int $index) use ($categories): Book {
                $book = Book::query()->create([
                    'title' => $seed[0],
                    'slug' => Str::slug($seed[0]).'-'.($index + 1),
                    'author' => $seed[1],
                    'publisher' => 'LibraFlow Academic Press',
                    'publication_year' => 2010 + ($index % 15),
                    'isbn' => '978623'.str_pad((string) ($index + 1000000), 7, '0', STR_PAD_LEFT),
                    'description' => 'A curated campus library title for '.$seed[2].' learners.',
                    'category_id' => $categories[$seed[2]]->id,
                ]);

                $copyCount = 2 + ($index % 4);
                for ($copyNumber = 1; $copyNumber <= $copyCount; $copyNumber++) {
                    BookCopy::query()->create([
                        'book_id' => $book->id,
                        'copy_code' => sprintf('LIB-%04d-%03d', $book->id, $copyNumber),
                        'shelf_location' => chr(65 + ($index % 5)).'-'.str_pad((string) ($copyNumber + 10), 2, '0', STR_PAD_LEFT),
                        'status' => BookCopy::STATUS_AVAILABLE,
                    ]);
                }

                $book->refreshCopyCounters();

                return $book;
            });

            $members = collect(range(1, 15))->map(function (int $number) use ($branches, $memberCategories): Member {
                $status = match (true) {
                    $number % 7 === 0 => Member::STATUS_REJECTED,
                    $number % 5 === 0 => Member::STATUS_PENDING,
                    default => Member::STATUS_APPROVED,
                };

                return Member::query()->create([
                    'member_code' => sprintf('MBR-%05d', $number),
                    'first_name' => ['Andi', 'Bella', 'Citra', 'Dion', 'Elsa', 'Farhan', 'Gita', 'Hendra', 'Intan', 'Joko', 'Kirana', 'Luthfi', 'Maya', 'Nanda', 'Oscar'][$number - 1],
                    'last_name' => ['Saputra', 'Permata', 'Sari', 'Wijaya', 'Putri', 'Akbar', 'Lestari', 'Pranata', 'Maheswari', 'Santoso', 'Anindya', 'Hakim', 'Utami', 'Firmansyah', 'Prakoso'][$number - 1],
                    'email' => 'member'.$number.'@libraflow.test',
                    'phone' => '0812'.str_pad((string) $number, 8, '0', STR_PAD_LEFT),
                    'roll_number' => 'STU-2026-'.str_pad((string) $number, 3, '0', STR_PAD_LEFT),
                    'branch_id' => $branches->values()[($number - 1) % $branches->count()]->id,
                    'year' => (($number - 1) % 4) + 1,
                    'member_category_id' => $memberCategories->values()[($number - 1) % $memberCategories->count()]->id,
                    'approved' => $status === Member::STATUS_APPROVED,
                    'rejected' => $status === Member::STATUS_REJECTED,
                    'approved_at' => $status === Member::STATUS_APPROVED ? now()->subDays($number) : null,
                    'rejected_at' => $status === Member::STATUS_REJECTED ? now()->subDays($number) : null,
                    'books_borrowed_count' => 0,
                ]);
            });

            $approvedMembers = $members->filter(fn (Member $member) => $member->approved && ! $member->rejected)->values();
            $availableCopies = BookCopy::query()->available()->orderBy('id')->take(12)->get();

            $availableCopies->take(4)->values()->each(function (BookCopy $copy, int $index) use ($approvedMembers, $admin): void {
                $member = $approvedMembers[$index % $approvedMembers->count()];
                $issuedAt = now()->subDays(3 + $index);

                $this->createTransaction($copy, $member, $admin, $issuedAt, $issuedAt->copy()->addDays($member->memberCategory->loan_days), null, BorrowTransaction::STATUS_BORROWED);
            });

            $availableCopies->slice(4, 3)->values()->each(function (BookCopy $copy, int $index) use ($approvedMembers, $librarian): void {
                $member = $approvedMembers[($index + 4) % $approvedMembers->count()];
                $issuedAt = now()->subDays(24 + $index);

                $this->createTransaction($copy, $member, $librarian, $issuedAt, $issuedAt->copy()->addDays($member->memberCategory->loan_days), null, BorrowTransaction::STATUS_OVERDUE);
            });

            $availableCopies->slice(7, 4)->values()->each(function (BookCopy $copy, int $index) use ($approvedMembers, $admin, $librarian): void {
                $member = $approvedMembers[($index + 7) % $approvedMembers->count()];
                $issuedAt = now()->subDays(18 + $index);
                $returnedAt = $issuedAt->copy()->addDays(6 + $index);

                $this->createTransaction($copy, $member, $admin, $issuedAt, $issuedAt->copy()->addDays($member->memberCategory->loan_days), $returnedAt, BorrowTransaction::STATUS_RETURNED, $librarian);
            });

            Book::query()->each(fn (Book $book) => $book->refreshCopyCounters());
            Member::query()->each(function (Member $member): void {
                $member->forceFill([
                    'books_borrowed_count' => $member->activeTransactions()->count(),
                ])->save();
            });
        });
    }

    private function createTransaction(
        BookCopy $copy,
        Member $member,
        User $issuedBy,
        mixed $issuedAt,
        mixed $dueAt,
        mixed $returnedAt,
        string $status,
        ?User $returnedBy = null,
    ): void {
        BorrowTransaction::query()->create([
            'transaction_code' => 'TRX-'.$issuedAt->format('Ymd').'-'.str_pad((string) (BorrowTransaction::query()->count() + 1), 4, '0', STR_PAD_LEFT),
            'book_copy_id' => $copy->id,
            'member_id' => $member->id,
            'issued_by' => $issuedBy->id,
            'returned_by' => $returnedBy?->id,
            'issued_at' => $issuedAt,
            'due_at' => $dueAt,
            'returned_at' => $returnedAt,
            'status' => $status,
            'notes' => $status === BorrowTransaction::STATUS_RETURNED ? 'Returned during seed setup.' : 'Seed transaction.',
        ]);

        if ($status === BorrowTransaction::STATUS_RETURNED) {
            return;
        }

        $copy->forceFill(['status' => BookCopy::STATUS_BORROWED])->save();
    }
}

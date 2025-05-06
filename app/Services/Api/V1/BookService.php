<?php

namespace App\Services\Api\V1;

use App\Models\Book;
use App\Models\PhysicalStock;
use App\Repositories\Api\V1\BookRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use Exception;

class BookService
{
    public function __construct(
        private readonly BookRepository $bookRepository
    ) {}

    public function createBook(array $data): Book
    {
        return DB::transaction(function () use ($data) {
            $book = $this->bookRepository->create([
                'title' => $data['title'],
                'author' => $data['author'],
                'description' => $data['description'] ?? null,
                'category_id' => $data['category_id'],
                'has_physical' => $data['has_physical'],
                'thumbnail' => $data['thumbnail'] ?? null,
                'ebook' => $data['ebook'] ?? null,
            ]);

            if ($data['has_physical']) {
                $this->bookRepository->createPhysicalStock($book, $data['quantity']);
            }

            return $book->load('physicalStock');
        });
    }

    public function updateBook(string $id, array $data): Book
    {
        $book = $this->bookRepository->find($id);

        if (!$book) {
            throw new ModelNotFoundException('Book not found');
        }

        return $this->bookRepository->update($book, [
            'title' => $data['title'] ?? $book->title,
            'author' => $data['author'] ?? $book->author,
            'description' => $data['description'] ?? $book->description,
            'category_id' => $data['category_id'] ?? $book->category_id,
            'ebook' => $data['ebook'],
            'thumbnail' => $data['thumbnail'] ?? '',
        ]);
    }

    public function deleteBook(string $id): void
    {
        $book = $this->bookRepository->find($id);

        if (!$book) {
            throw new ModelNotFoundException('Book not found');
        }

        if ($this->bookRepository->hasLoans($book)) {
            throw new Exception('Cannot delete book with active loans', Response::HTTP_FORBIDDEN);
        }

        $this->bookRepository->delete($book);
    }

    public function increaseStock(string $id, int $quantity): array
    {
        $book = $this->bookRepository->find($id);

        if (!$book) {
            throw new ModelNotFoundException('Book not found');
        }

        return DB::transaction(function () use ($book, $quantity) {
            if (!$book->has_physical) {
                $this->bookRepository->update($book, ['has_physical' => true]);
            }

            $physicalStock = $this->bookRepository->updateOrCreatePhysicalStock($book, $quantity);

            return [
                'quantity' => $physicalStock->quantity
            ];
        });
    }
}

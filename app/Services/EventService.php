<?php

namespace App\Services;

use App\Models\Event;
use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use File;

class EventService {

    public function filter(array $eventData): Collection
    {
        $query = Event::with(['organizer', 'genres']);

        if (isset($eventData['verified']) && $eventData['verified'] === 'false') {
            $query->where('is_verified', false)->latest();
        } else {
            $query->where('is_verified', true)
                ->where('date', '>=', now()->toDateString()); 
        }

        if (!empty($eventData['search'])) {
            $search = '%' . $eventData['search'] . '%';
            $query->where(function($each) use ($search) {
                $each->where('title', 'like', $search)
                ->orWhere('lineup', 'like', $search)
                ->orWhere('location_name', 'like', $search); 
            });
        }

        if (!empty($eventData['neighborhood'])) {
            $query->where('neighborhood', $eventData['neighborhood']);
        }

        if (!empty($eventData['genre'])) {
            $query->whereHas('genres', function($genreQuery) use ($eventData) {
                $genreQuery->where('genres.id', $eventData['genre']);
            });
        }

        $order = $eventData['price'] ?? null;
        $order === 'asc' || $order === 'desc' ? $query->orderBy('price', $order) : $query->orderBy('date', 'asc');

        return $query->get();
    }

    /**
     * @param User $user
     * @param array $eventData
     * @param UploadedFile|null $file
     * @param string|null $base64Image
     */
    public function store(User $user, array $eventData, $file = null, string $base64Image = null): Event
    {
        if ($user->role === UserRole::CLUBBER) {
            $activeEventsCount = $user->events()
                ->where('date', '>=', now()->toDateString())
                ->count();

            if ($activeEventsCount >= 1) {
                throw ValidationException::withMessages([
                    'limit' => 'As a Clubber, you can only have one active event. Get verified to unlock more slots!'
                ]);
            }
        }

        $eventData['price_info'] = $this->processPriceInfo($eventData);
        $eventData['stamp_token'] = Str::random(32); 
        $eventData['is_verified'] = false; 

     
        if ($base64Image) {
            $eventData['flyer'] = $this->handleBase64Upload($base64Image);
        } elseif ($file instanceof UploadedFile) {
            $eventData['flyer'] = $this->handleFileUpload($file);
        }

        if (isset($eventData['start_time'])) {
            $eventData['start_time'] = Carbon::parse($eventData['start_time'])->format('H:i:s');
        }
        
        if (isset($eventData['end_time'])) {
            $eventData['end_time'] = Carbon::parse($eventData['end_time'])->format('H:i:s');
        }

        $event = $user->events()->create($eventData);

        if (!empty($eventData['genres'])) {
            $event->genres()->attach($eventData['genres']);
        }

        return $event;
    }

    /**
     * @param User $user
     * @param Event $event
     * @param array $eventData
     * @param UploadedFile|null $file
     * @param string|null $base64Image
     */
    public function update(User $user, Event $event, array $eventData, $file = null, string $base64Image = null): Event
    {
        $eventData['price_info'] = $this->processPriceInfo($eventData);

    
        if ($base64Image || $file instanceof UploadedFile) {
            if ($event->flyer) {
                if (str_contains($event->flyer, 'res.cloudinary.com')) {
                    try {
                        $publicId = pathinfo(parse_url($event->flyer, PHP_URL_PATH), PATHINFO_FILENAME);
                        $cloudinary = new \Cloudinary\Cloudinary(env('CLOUDINARY_URL'));
                        $cloudinary->uploadApi()->destroy($publicId);
                    } catch (\Exception $e) {}
                } elseif (file_exists(public_path($event->flyer))) {
                    unlink(public_path($event->flyer));
                }
            }

            $eventData['flyer'] = $base64Image 
                ? $this->handleBase64Upload($base64Image) 
                : $this->handleFileUpload($file);
        }

        if ($user->role !== UserRole::ADMIN) {
            $eventData['is_verified'] = false;
        }

        $event->update($eventData);

        if (isset($eventData['genres'])) {
            $event->genres()->sync($eventData['genres']);
        }

        return $event;
    }

    public function delete(Event $event): bool
    {
        if ($event->flyer) {
            if (str_contains($event->flyer, 'res.cloudinary.com')) {
                try {
                    $publicId = pathinfo(parse_url($event->flyer, PHP_URL_PATH), PATHINFO_FILENAME);
                    $cloudinary = new \Cloudinary\Cloudinary(env('CLOUDINARY_URL'));
                    $cloudinary->uploadApi()->destroy($publicId);
                } catch (\Exception $e) {}
            } elseif (file_exists(public_path($event->flyer))) {
                unlink(public_path($event->flyer));
            }
        }
        return $event->delete();
    }

    /**
     * Process base64-encoded image uploads, saving them to Cloudinary and returning the file path.
     */
    private function handleBase64Upload(string $base64Image): ?string
    {
        try {
            $cloudinary = new \Cloudinary\Cloudinary(env('CLOUDINARY_URL'));
            $response = $cloudinary->uploadApi()->upload($base64Image);
            return $response['secure_url'];
        } catch (\Exception $e) {
            \Log::error('Error uploading base64 to Cloudinary: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Process file uploads in traditional multipart/form-data requests
     */
    private function handleFileUpload(UploadedFile $file): string
    {
        $cloudinary = new \Cloudinary\Cloudinary(env('CLOUDINARY_URL'));
        $response = $cloudinary->uploadApi()->upload($file->getRealPath());
        return $response['secure_url'];
    }

    private function processPriceInfo(array $eventData): string 
    {
        if (!empty($eventData['price_info'])) return $eventData['price_info'];
        return ($eventData['price'] ?? 0) == 0 ? 'Entrada gratuita' : '';
    }
}
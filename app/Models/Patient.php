<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['first_name', 'last_name', 'email', 'phone', 'date_of_birth', 'gender', 'address', 'insurance_provider', 'notes'])]
class Patient extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return ['date_of_birth' => 'date'];
    }

    protected function fullName(): Attribute
    {
        return Attribute::get(fn () => "{$this->first_name} {$this->last_name}");
    }

    protected function age(): Attribute
    {
        return Attribute::get(fn () => $this->date_of_birth?->age);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /** Search by name, email or phone. "Jane Doe" matches first and last name together (portable across MySQL, Postgres and SQLite). */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);
        if ($term === '') {
            return $query;
        }
        $like = fn (string $v) => '%'.str_replace(['%', '_'], ['\%', '\_'], $v).'%';
        $parts = preg_split('/\s+/', $term, 2);

        return $query->where(function (Builder $q) use ($term, $parts, $like) {
            $q->where('first_name', 'like', $like($term))
                ->orWhere('last_name', 'like', $like($term))
                ->orWhere('email', 'like', $like($term))
                ->orWhere('phone', 'like', $like($term));
            if (count($parts) === 2) {
                $q->orWhere(fn (Builder $w) => $w->where('first_name', 'like', $like($parts[0]))->where('last_name', 'like', $like($parts[1])));
            }
        });
    }

    public function outstandingBalance(): float
    {
        return (float) $this->invoices()->where('status', 'unpaid')->sum('total');
    }
}

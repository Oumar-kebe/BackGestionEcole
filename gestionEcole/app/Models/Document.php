<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Document extends Model
{
    use HasFactory;

    protected $fillable = [
        'nom',
        'type',
        'fichier',
        'documentable_type',
        'documentable_id'
    ];

    // Relations
    public function documentable()
    {
        return $this->morphTo();
    }

    // Events
    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($document) {
            Storage::delete($document->fichier);
        });
    }

    // Helpers
    public function getUrlAttribute()
    {
        return Storage::url($this->fichier);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CsvUpload extends Model
{
    use HasFactory;

    protected $fillable = [
        'olympiad_id',
        'original_file_name',
        'successful_records',
        'failed_records',
        'header_errors',
        'competitor_errors',
        'total_records',
        'file_path',
        'error_file_path',
        'file_size'
    ];

    protected $casts = [
        'successful_records' => 'integer',
        'failed_records' => 'integer',
        'header_errors' => 'integer',
        'competitor_errors' => 'integer',
        'total_records' => 'integer',
        'file_size' => 'integer',
    ];

    /**
     * Get the olympiad that owns the CSV upload
     */
    public function olympiad()
    {
        return $this->belongsTo(Olympiad::class);
    }

    /**
     * Get the success rate as a percentage
     */
    public function getSuccessRateAttribute()
    {
        if ($this->total_records === 0) {
            return 0;
        }
        return round(($this->successful_records / $this->total_records) * 100, 2);
    }

    /**
     * Check if the upload has errors
     */
    public function hasErrors()
    {
        return $this->failed_records > 0;
    }

    /**
     * Check if the upload has header errors
     */
    public function hasHeaderErrors()
    {
        return $this->header_errors > 0;
    }

    /**
     * Check if the upload has competitor errors
     */
    public function hasCompetitorErrors()
    {
        return $this->competitor_errors > 0;
    }

    /**
     * Check if error file exists
     */
    public function hasErrorFile()
    {
        return !empty($this->error_file_path);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Url extends Model
{
    use HasFactory, HasSlug;

    protected $table = 'urls';
    protected $fillable = ['original_url' , 'alias' , 'status' , 'redirect_logs'] ;

    protected  $casts = [
        'redirect_logs' => 'array',
    ];

    /**
     * Get the options for generating the slug.
     */
    public function getSlugOptions(): SlugOptions
    {
       return SlugOptions::create()
       ->generateSlugsFrom(function (){
           $parsedUrl = parse_url($this->original_url);
           return $parsedUrl['host'] ?? 'link';
       })
       ->saveSlugsTo('alias')
       ->slugsShouldBeNoLongerThan(10)
       ->usingSeparator('-') ;
    }

    /**
     * Add a redirect log entry to the URL.
     *
     * @param array $logData
     * @return void
     */
    public function addRedirectLog(array $logData)
    {
        $logs = $this->redirect_logs ?? [];
        $logs[] = $logData;
        $this->redirect_logs = $logs;
        $this->save();
    }

    /**
     * Get the total number of redirects.
     *
     * @return int
     */
    public function getRedirectCount()
    {
        return count($this->redirect_logs ?? []);
    }

    /**
     * Get daily redirect breakdown.
     *
     * @return array
     */
    public function getDailyBreakdown()
    {
        $logs = $this->redirect_logs ?? [];
        $daily = [];

        foreach ($logs as $log) {
            $date = date('Y-m-d', strtotime($log['accessed_at']));
            $daily[$date] = ($daily[$date] ?? 0) + 1;
        }

        return $daily;
    }

}

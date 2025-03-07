<?php

namespace App\Http\Controllers;

use App\Models\Url;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redirect;

class UrlController extends Controller
{

    /**
     * Shorten a URL and return the shortened version.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function shorten(Request $request)
    {
        $request->validate([
            'original_url' => 'required|url|max:2048',
            'alias' => 'nullable|string|max:10|unique:urls,alias',
        ]);

        try {
            $urlData = [
                'original_url' => $request->original_url,
                'status' => 'active',
                'redirect_logs' => [],
            ];

            if ($request->alias) {
                $urlData['alias'] = $request->alias; // Use custom alias if provided
            }

            $url = Url::create($urlData);

            // Generate slug if no custom alias was provided
            if (!$request->alias) {
                $url->generateSlug();
                $url->save();
            }

            $shortenedUrl = url('/' . $url->alias);

            Log::info('URL shortened', ['alias' => $url->alias, 'original_url' => $url->original_url]);

            return response()->json([
                'success' => true,
                'original_url' => $url->original_url,
                'alias' => $url->alias,
                'shortened_url' => $shortenedUrl,
            ], 201);
        } catch (\Exception $e) {
            Log::error('Failed to shorten URL: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Unable to shorten URL',
            ], 500);
        }
    }





    /**
     * Redirect to the original URL based on the alias.
     *
     * @param string $alias
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function redirect($alias)
    {
        // Return the Url while using Cache
        $original_url = Cache::remember("url:{$alias}", 86400, function () use ($alias) {
            $url = Url::where('alias', $alias)
                ->where('status' , 'active')
                ->first();

//            return $url ? $url->original_url : null ;
        });


        if (!$original_url) {
            Log::warning('Redirect failed: URL not found or inactive', ['alias' => $alias]);
            return response()->json(['message' => 'URL not found or inactive'], 404);
        }


        // Count and log each redirection for analytics purposes.
        Queue::push(function() use ($alias){

            $url = Url::where('alias', $alias)
                ->where('status' , 'active')
                ->first();

            if($url){
                $url->addRedirectLog([
                    'accessed_at' => now()->toDateTimeString(),
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ]);
                Log::info('Redirect logged', ['alias' => $alias]);
            }
        }) ;
        return Redirect::away($original_url, 301);
    }


    /**
     * Retrieve analytics for a given alias.
     *
     * @param string $alias
     * @return \Illuminate\Http\JsonResponse
     */
    public function analytics($alias)
    {
        $url = Url::where('alias', $alias)->where('status', 'active')->first();

        if (!$url) {
            Log::warning('Analytics failed: URL not found or inactive', ['alias' => $alias]);
            return response()->json(['message' => 'URL not found or inactive'], 404);
        }

        $analytics = Cache::remember("analytics:{$alias}", 3600, function () use ($url) {
            return [
                'total_redirects' => $url->getRedirectCount(),
                'daily' => $url->getDailyBreakdown(),
            ];
        });

        return response()->json([
            'success' => true,
            'alias' => $alias,
            'original_url' => $url->original_url,
            'analytics' => $analytics,
        ]);
    }
}

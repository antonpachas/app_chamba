<?php

namespace App\Http\Controllers;

use App\Models\ProviderService;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

final class SitemapController extends Controller
{
    public function index(): Response
    {
        $listings = ProviderService::query()
            ->visible()
            ->select(['id', 'title', 'updated_at'])
            ->orderByDesc('updated_at')
            ->limit(49_000)
            ->get();

        $appBase = rtrim(url('/app'), '/');

        $lines = ['<?xml version="1.0" encoding="UTF-8"?>'];
        $lines[] = '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        foreach (['' , '/buscar', '/registro', '/acceder'] as $path) {
            $lines[] = '<url>'
                .'<loc>'.e($appBase.$path).'</loc>'
                .'<changefreq>daily</changefreq>'
                .'<priority>'.($path === '' ? '1.0' : '0.8').'</priority>'
                .'</url>';
        }

        foreach ($listings as $listing) {
            $slug = Str::slug($listing->title).'-'.$listing->id;
            $loc  = $appBase.'/anuncio/'.rawurlencode($slug);
            $lastmod = $listing->updated_at?->toDateString() ?? '';
            $lines[] = '<url>'
                .'<loc>'.e($loc).'</loc>'
                .($lastmod ? "<lastmod>{$lastmod}</lastmod>" : '')
                .'<changefreq>weekly</changefreq>'
                .'<priority>0.6</priority>'
                .'</url>';
        }

        $lines[] = '</urlset>';

        return response(implode('', $lines), 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}

<?php

namespace App\Providers;

use App\Models\BlokLahan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Share notifikasi blok kritis (E3) dan admin ke semua view yang pakai layout app
        View::composer('layouts.app', function ($view) {
            $admin = Auth::guard('admin')->user();

            $blokDarurat = BlokLahan::whereHas('rekomendasiRbsTerbaru', function ($q) {
                $q->where('status_kebutuhan_dominan', 'Darurat');
            })->with(['anggota', 'kondisiTerbaru', 'rekomendasiRbsTerbaru'])->get();

            // Filter out blocks where the latest conditions are newer than the latest recommendations (outdated)
            $blokDarurat = $blokDarurat->filter(function ($blok) {
                $kondisi = $blok->kondisiTerbaru;
                $rbs = $blok->rekomendasiRbsTerbaru;
                if (! $kondisi || ! $rbs) {
                    return false;
                }

                return ! $kondisi->updated_at->gt($rbs->updated_at);
            });

            $jumlahDarurat = $blokDarurat->count();
            $blokDaruratLimit = $blokDarurat->take(5);

            $view->with('notifBlokDarurat', $blokDaruratLimit);
            $view->with('jumlahNotifDarurat', $jumlahDarurat);
            $view->with('admin', $admin);
        });
    }
}

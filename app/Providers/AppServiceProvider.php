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
        // Share notifikasi dan admin ke semua view yang pakai layout app
        View::composer('layouts.app', function ($view) {
            $admin = Auth::guard('admin')->user();

            // Blok defisiensi berat (notifikasi lama)
            $blokDarurat = BlokLahan::whereHas('rekomendasiRbsTerbaru', function ($q) {
                $q->where('status_kebutuhan_dominan', 'Darurat');
            })->with(['anggota', 'kondisiTerbaru', 'rekomendasiRbsTerbaru'])->get();

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

            // Pahan v2.8: Notifikasi database (unread count)
            $unreadNotifCount = $admin ? $admin->unreadNotifications()->count() : 0;
            $totalNotifBadge = $jumlahDarurat + $unreadNotifCount;

            $view->with('notifBlokDarurat', $blokDaruratLimit);
            $view->with('jumlahNotifDarurat', $jumlahDarurat);
            $view->with('unreadNotifCount', $unreadNotifCount);
            $view->with('totalNotifBadge', $totalNotifBadge);
            $view->with('admin', $admin);
        });
    }
}

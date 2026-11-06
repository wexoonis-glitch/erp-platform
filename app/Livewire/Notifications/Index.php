<?php
namespace App\Livewire\Notifications;
use App\Models\SysNotification;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class Index extends Component {
    public function markAsRead(string $id) {
        $notif = SysNotification::findOrFail($id);
        if ($notif->user_id === Auth::id()) {
            $notif->update(['is_read' => true, 'read_at' => now()]);
        }
    }

    public function markAllAsRead() {
        SysNotification::where('user_id', Auth::id())->where('is_read', false)->update(['is_read' => true, 'read_at' => now()]);
    }

    #[Layout('layouts.app')]
    public function render() {
        $notifications = SysNotification::where('user_id', Auth::id())
            ->orderByDesc('created_at')
            ->limit(50)
            ->get()
            ->toArray();

        return view('livewire.notifications.index', compact('notifications'))->layout('layouts.app', ['title' => 'Bildirimler']);
    }
}

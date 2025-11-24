<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{

    public function index()
    {
        return view("notification_test");
    }
    public function getUserNotifications()
    {

        //    if(Auth::check()){
        
        $notifications = Notification::where('user_id', Auth::user()->id)
        ->where('is_read', 0)
        ->orderBy('created_at', 'desc')
        ->get();

        if ($notifications->isEmpty()) {
            return response()->json([
                'message' => 'You got no new Notifications'
            ],404);
        } else {
            // Format the notifications
            $formattedNotifications = $notifications->map(function ($notification) {
                $detail = json_decode($notification->detail, true);
                // $user=User::where('id','=',$creator_id)->first();
                return [
                    'id' => $notification->id,
                    'type' => $notification->type,
                    'log_type'=> $notification->log_type,
                    'title' => $notification->notification_for ?? '',
                    'msg' => $notification->message ?? '',
                    'created_at' => $notification->created_at->diffForHumans(),
                    // 'Created_by_name' =>$user->name,
                ];
            });
            return response()->json($formattedNotifications ,200);
        }

    }
    // else{
//     return response()->json(['message'=> ''],404);
// }

    public function hasSeen($id)
    {

        $notifications = Notification::where('id', $id)->first();
        // dd($notifications);
        if($notifications->is_read == 1){
            return response()->json([$notifications, 200]);
        }
        else{
            $notifications->is_read = 1;
        $notifications->save();
        }

        return response()->json($id, 200);
    }
}

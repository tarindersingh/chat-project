<?php
/**
 * Created by PhpStorm.
 * User: tarin
 * Date: 28-06-2020
 * Time: 08:29 PM
 */

namespace App\Models;


use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;

/**
 * Class ChatMessage
 * @package App\Models
 * @property int $id
 * @property int $from
 * @property int $to
 * @property string $message
 * @property Carbon $updated_at
 * @property Carbon $created_at
 * @property-read User $toUser
 */
class ChatMessage extends Model
{

    public function toUser()
    {
        return $this->belongsTo(User::class, 'to', 'id');
    }


    public function toFirebase()
    {

        $factory = (new Factory())->withServiceAccount('../storage/app/chat-test-77351-firebase-adminsdk-aqes1-1384e4341e.json');

        $messaging = $factory->createMessaging();


        $toUser = $this->toUser;
        $deviceTokens = $toUser->deviceTokens;

        $notification = Notification::create($toUser->name, $this->message);

        foreach ($deviceTokens as $deviceToken) {
            $message = CloudMessage::withTarget('token', $deviceToken->device_token)
                ->withNotification($notification)
                ->withData([
                    'title' => $toUser->name,
                    'body' => $this->message
                ]);

            try {
                $messaging->send($message);
            } catch (\Exception $e) {
                $deviceToken->delete();
            }
        }
    }

}
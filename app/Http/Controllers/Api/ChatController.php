<?php
/**
 * Created by PhpStorm.
 * User: tarin
 * Date: 28-06-2020
 * Time: 04:04 PM
 */

namespace App\Http\Controllers\Api;


use App\Helpers\Ajax;
use App\Http\Controllers\Controller;
use App\Models\ChatMessage;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ChatController extends Controller
{

    public function createMessage(Request $request, Ajax $ajax)
    {
        $validator = Validator::make($request->all(), [
            'id' => [
                'required',
                'exists:' . User::class . ',id',
                Rule::notIn([Auth::id()]),
            ],
            'message' => 'required'
        ]);

        if ($validator->fails()) {
            return $ajax
                ->field_errors($validator)
                ->setStatusCode(Response::HTTP_BAD_REQUEST)
                ->send();
        }

        $cm = new ChatMessage();
        $cm->from = Auth::id();
        $cm->to = $request->input('id');
        $cm->message = $request->input('message');
        $cm->save();
        return $ajax->success()
            ->message("Message Sent!")
            ->send();
    }

    public function getMessages(Request $request, Ajax $ajax)
    {

        $validator = Validator::make($request->all(), [
            'id' => [
                'required',
                'exists:' . User::class . ',id'
            ]
        ]);
        if ($validator->fails()) {
            return $ajax
                ->field_errors($validator)
                ->setStatusCode(Response::HTTP_BAD_REQUEST)
                ->send();
        }

        $id = $request->input('id');
        $page = intval($request->input('page', 1));
        $perPage = intval($request->input('per-page', 10));

        $cms = ChatMessage::query()
            ->where(function ($subQuery) {
                $subQuery->where('from', '=', Auth::id())
                    ->orWhere('to', '=', Auth::id());
            })
            ->where(function ($subQuery) use ($id) {
                $subQuery->where('from', '=', $id)
                    ->orWhere('to', '=', $id);
            })
            ->forPage($page, $perPage)
            ->get();
        $ajax->param('page', $page);
        $ajax->param('per-page', $perPage);
        return $ajax->param('messages', $cms)->success()->send();
    }

}
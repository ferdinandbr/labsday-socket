<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Events\NewMessage;
use Carbon\Carbon;

class MessageController extends Controller
{
    public function newMessage(Request $request)
    {
      $data =  [
        'message' => $request->message,
        'userName' => auth()->user(),
        'date' => Carbon::now()->format('d-m-Y H:i:s')
      ];

      event(new NewMessage($data));

      return response()->json(['success' => true, 'message' => 'Mensagem enviada com sucesso']);
    }
}

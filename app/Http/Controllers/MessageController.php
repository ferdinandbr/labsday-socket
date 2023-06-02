<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Events\NewMessage;
use Carbon\Carbon;
use Faker\Generator;
use Illuminate\Container\Container;

class MessageController extends Controller
{
    public function newMessage(Request $request)
    {
      $faker = Container::getInstance()->make(Generator::class);
      $data =  [
        'message' => $request->message,
        'userName' => $faker->name(),
        'date' => Carbon::now()->format('d-m-Y H:i:s')
      ];

      event(new NewMessage($data));

      return response()->json(['success' => true, 'message' => 'Mensagem enviada com sucesso']);
    }
}

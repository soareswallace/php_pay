<?php

namespace App\Http\Controllers;

use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;

class UsersController extends Controller
{
    const AUTHORIZATION_URL = 'https://run.mocky.io/v3/8fafdd68-a090-496f-8c9a-3442cf30dae6';
    const PAYER_KEY = 'payer';
    const PAYEE_KEY = 'payee';
    const VALUE_KEY = 'value';
    const MESSAGE_KEY = 'message';

    public function printPayerName(Request $request)
    {
        $payerId = $request->input('payer');
        $user = User::find($payerId)->toJson(JSON_PRETTY_PRINT);


        return response()->json([
            $user
        ], 200);
    }

    public function isCNPJ(Request $request)
    {
        $payerId = $request->input('id');

        $user = User::find($payerId);

        return response()->json([
            $user->isCNPJ ? "true" : "false"
        ], 200);
    }

    public function transaction(Request $httpRequest)
    {
        try {
            $request = $httpRequest->json()->all();

            $payerId = $request[self::PAYER_KEY];
            $payeeId = $request[self::PAYEE_KEY];
            $value = $request[self::VALUE_KEY];

            /** @var User $user */
            $payer = User::find($payerId);
            $payee = User::find($payeeId);

            if (!$payer->isCNPJ && ($payer->saldo - $value >= 0)) { // TODO mudar a logica para uma funçao privada
                DB::beginTransaction();

                DB::table('users')->where('id', $payer->id) //TODO funcao privada
                    ->update(['saldo' => $payer->saldo - $value]);
                DB::table('users')->where('id', $payee->id)
                    ->update(['saldo' => $payee->saldo + $value]);

                $authorization = Http::get(self::AUTHORIZATION_URL)->json(); //TODO MUDAR A CHECAGEM PARA UMA FUNCAO PRIVADA

                if ($authorization[self::MESSAGE_KEY] === 'Autorizado') {
                    DB::commit();

                    return response()->json([
                        $authorization[self::MESSAGE_KEY]
                    ], 200);
                } else {
                    throw new HttpException(403, 'Não autorizado');
                }
            } else {
                return response()->json([
                    "Operação não permitida"
                ], 403);
            }
        } catch (\Exception $exception) {
            DB::rollback();
            return response()->json([
                "Erro desconhecido"
            ], $exception->getCode());
        }
    }
}

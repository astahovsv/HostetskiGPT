<?php

namespace Modules\HostetskiGPT\Http\Controllers;

use App\Thread;
use App\Mailbox;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\HostetskiGPT\Entities\Settings;

class HostetskiGPTController extends Controller {

    public function generate(Request $request) {
        if (Auth::user() === null) return Response::json(["error" => "Unauthorized"], 401);
        $settings = Settings::findOrFail($request->get("mailbox_id"));

        $client = \OpenAI::factory()
            ->withApiKey($settings->api_key)
            ->withHttpClient(new \GuzzleHttp\Client(\Helper::setGuzzleDefaultOptions()))
            ->withHttpHeader('OpenAI-Beta', 'assistants=v2')
            ->make();

        // Создаем поток
        $gptThread = $client->threads()->create([]);
        
        // Создаем сообщения
        $client->threads()->messages()->create($gptThread->id, [
            'role' => 'user',
            'content' => $request->get('query'),
        ]);

        // Выполняем обработку и ждем
        $stream = $client->threads()->runs()->createStreamed(
            threadId: $gptThread->id,
            parameters: [
                'assistant_id' => $settings->assistant_id,
            ],
        );
        foreach($stream as $response) {
            $a = $response->event;
            $b = $response->response;
        };

        // Получаем последнее сообщение
        $gptMessages = $client->threads()->messages()->list($gptThread->id, [
            'limit' => 1,
        ]);

        // Получаем ответ
        $gptAnswer = $gptMessages->data[0]->content[0]->text->value;
        
        // Сохраняем ответ в DB
        $thread = Thread::find($request->get('thread_id'));
        $answers = [];
        if ($thread->chatgpt != null) {
            $answers = json_decode($thread->chatgpt, true);
        }
        array_push($answers, trim($gptAnswer, "\n"));
        $thread->chatgpt = json_encode($answers, JSON_UNESCAPED_UNICODE);
        $thread->save();

        // Возвращаем ответ
        return Response::json([
            'query' => $request->get('query'),
            'answer' => $gptAnswer
        ], 200);
    }

    public function answers(Request $request) {
        if (Auth::user() === null) return Response::json(["error" => "Unauthorized"], 401);
        $conversation = $request->query('conversation');
        $threads = Thread::where("conversation_id", $conversation)->get();
        $result = [];
        foreach ($threads as $thread) {
            if ($thread->chatgpt !== "{}" && $thread->chatgpt !== null) {
                $answers = [];
                $answers_text = json_decode($thread->chatgpt, true);
                if ($answers_text === null) continue;
                foreach ($answers_text as $answer_text) {
                    array_push($answers, $answer_text);
                }
                $answer = ["thread" => $thread->id, "answers" => $answers];
                array_push($result, $answer);
            }
        }
        return Response::json(["answers" => $result], 200);
    }

    public function settings($mailbox_id) {
        $mailbox = Mailbox::findOrFail($mailbox_id);

        $settings = Settings::find($mailbox_id);

        if (empty($settings)) {
            $settings['mailbox_id'] = $mailbox_id;
            $settings['api_key'] = "";
            $settings['assistant_id'] = "";
        }

        return view('hostetskigpt::settings', [
            'mailbox'   => $mailbox,
            'settings'  => $settings
        ]);
    }

    public function saveSettings($mailbox_id, Request $request) {

        Settings::updateOrCreate(
            ['mailbox_id' => $mailbox_id],
            [
                'api_key' => $request->get('api_key'),
                'assistant_id' => $request->get('assistant_id')
            ]
        );

        return redirect()->route('hostetskigpt.settings', ['mailbox_id' => $mailbox_id]);
    }
}

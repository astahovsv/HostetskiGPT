<?php

namespace Modules\HostetskiGPT\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use App\Thread;
use App\Mailbox;
use Modules\HostetskiGPT\Entities\GPTSettings;

class HostetskiGPTController extends Controller
{

    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function index()
    {
        return view('hostetskigpt::index');
    }

    /**
     * Show the form for creating a new resource.
     * @return Response
     */
    public function create()
    {
        return view('hostetskigpt::create');
    }

    /**
     * Store a newly created resource in storage.
     * @param  Request $request
     * @return Response
     */
    public function store(Request $request)
    {
    }

    /**
     * Show the specified resource.
     * @return Response
     */
    public function show()
    {
        return view('hostetskigpt::show');
    }

    /**
     * Show the form for editing the specified resource.
     * @return Response
     */
    public function edit()
    {
        return view('hostetskigpt::edit');
    }

    /**
     * Update the specified resource in storage.
     * @param  Request $request
     * @return Response
     */
    public function update(Request $request)
    {
    }

    /**
     * Remove the specified resource from storage.
     * @return Response
     */
    public function destroy()
    {
    }

    public function generate(Request $request) {
        if (Auth::user() === null) return Response::json(["error" => "Unauthorized"], 401);
        $settings = GPTSettings::findOrFail($request->get("mailbox_id"));

        $client = \OpenAI::factory()
            ->withApiKey($settings->api_key)
            ->withHttpClient(new \GuzzleHttp\Client([
                    'timeout' => config('app.curl_timeout'),
                    'connect_timeout' => config('app.curl_connect_timeout'),
                    'proxy' => config('app.proxy'),
                ]))
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
                'assistant_id' => 'asst_1SBgvdStUpzj9GobOmeRkixd',
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

        $settings = GPTSettings::find($mailbox_id);

        if (empty($settings)) {
            $settings['mailbox_id'] = $mailbox_id;
            $settings['api_key'] = "";
            $settings['token_limit'] = "";
            $settings['start_message'] = "";
            $settings['enabled'] = false;
            $settings['model'] = "";
            $settings['client_data_enabled'] = false;
        }

        return view('hostetskigpt::settings', [
            'mailbox'   => $mailbox,
            'settings'  => $settings
        ]);
    }

    public function saveSettings($mailbox_id, Request $request) {
        //return $request->get('model');
        GPTSettings::updateOrCreate(
            ['mailbox_id' => $mailbox_id],
            [
                'api_key' => $request->get("api_key"),
                'enabled' => isset($_POST['gpt_enabled']),
                'token_limit' => $request->get('token_limit'),
                'start_message' => $request->get('start_message'),
                'model' => $request->get('model'),
                'client_data_enabled' => isset($_POST['show_client_data_enabled'])
            ]
        );

        return redirect()->route('hostetskigpt.settings', ['mailbox_id' => $mailbox_id]);
    }

    public function checkIsEnabled(Request $request) {
        $settings = GPTSettings::find($request->query("mailbox"));
        if (empty($settings)) {
            return Response::json(['enabled'=> false], 200);
        }
        return Response::json(['enabled' => $settings['enabled']], 200);
    }

}

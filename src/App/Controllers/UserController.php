<?php

namespace App\Controllers;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

use GuzzleHttp;

use Twitter_Extractor;

class UserController
{
    protected $userService;
    protected $config;
    protected $guzzleBody = [];
    protected $me = null;
    protected $user = null;

    /**
     * @param $service
     * @param $config
     */
    public function __construct($service, $config)
    {
        $this->userService = $service;
        $this->config = $config;
        $this->guzzleBody['token'] = $this->config['token'];
    }

    /**
     * @return JsonResponse
     */
    public function getAll()
    {
        return new JsonResponse($this->userService->getAll());
    }

    /**
     * @param array $data
     *
     * @return array
     */
    public function get(array $data)
    {
        $dbUser = $this->userService->getBy($data);

        if (!count($dbUser)) {
            $this->populateUsers();
        }

        return array_shift($dbUser);
    }

    public function getDones(Request $request)
    {
        $data = $request->request->all();

        // No POST data sent
        if (!count($data)) {
            return new JsonResponse([
                'status' => false,
                'message' => 'Invalid data.',
            ], 412);
        }

        if (isset($data['command']) && !empty($data['command'])) {
            if (isset($this->config['command_tokens'][$data['command']])) {
                $this->parseCommand($data);
            }
        }

        return new JsonResponse([
            'status' => false,
            'message' => 'Command is a required parameter.',
        ], 412);
    }

    /**
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function save(Request $request)
    {
        $user = $this->getDataFromRequest($request);

        return new JsonResponse(['id' => $this->userService->save($user)]);
    }

    /**
     * @param integer $id
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function update($id, Request $request)
    {
        $user = $this->getDataFromRequest($request);
        $this->userService->update($id, $user);

        return new JsonResponse($user);

    }

    /**
     * @param integer $id
     *
     * @return JsonResponse
     */
    public function delete($id)
    {
        return new JsonResponse($this->userService->delete($id));
    }

    /**
     * @param Request $request
     *
     * @return array
     */
    public function getDataFromRequest(Request $request)
    {
        return $user = [
            'user' => $request->request->get('user')
        ];
    }

    /**
     * @param string $text
     *
     * @return array
     */
    private function parseText($text)
    {
        $extractor = new Twitter_Extractor();

        return $extractor->extractMentionedScreennames($text);
    }

    private function populateUsers()
    {
        $client = new GuzzleHttp\Client();
        $response = $client->post($this->config['endpoints']['api'].'users.list', [
            'body' => [
                'token' => $this->config['token']
            ]
        ]);

        $json = $response->json();

        if (count($json['members'])) {
            foreach ($json['members'] as $member) {
                $user = $this->userService->getBy(['name', $member['name']]);

                if (!$user) {
                    $this->userService->save([
                        'name' => $member['name'],
                        'slack_id' => $member['id'],
                    ]);
                }
            }
        }
    }

    /**
     * @param array $data
     * @return JsonResponse
     */
    private function parseCommand(array $data)
    {
        $commandToken = $this->config['command_tokens'][$data['command']]['token'];
        if ($commandToken != $data['token']) {
            return new JsonResponse([
                'status' => false,
                'message' => 'Invalid command token.',
            ], 403);
        }

        $json = $this->getDataFromApi($data);
        $messages = $this->processDataFromApi($json);

        $this->postMessages(array_reverse($messages));
    }

    /**
     * @param $data
     * @return mixed
     */
    private function getDataFromApi($data)
    {
        $channel = $this->config['command_tokens'][$data['command']]['channel'];

        if ($data['text']) {
            $this->user = $this->parseText($data['text'])[0];
        }

        $this->user = $this->get(['name', $this->user]);

        if ($data['user_id']) {
            $this->me = $this->get(['slack_id', $data['user_id']]);
        }

        $today = new \DateTime();
        if ($today->format('l') == 'Monday') {
            $startAt = new \DateTime('- 3 days');
        } else {
            $startAt = new \DateTime('yesterday');
        }
        $startAt->setTime(0, 0, 0);

        $client = new GuzzleHttp\Client();

        $this->guzzleBody['channel'] = $channel;
        $this->guzzleBody['oldest'] = $startAt->format('U').'.000000';

        $response = $client->post($this->config['endpoints']['api'].'channels.history', [
            'body' => $this->guzzleBody,
        ]);

        $json = $response->json();

        return $json;
    }

    /**
     * @param $json
     * @return array
     */
    private function processDataFromApi($json)
    {
        $messages = [];

        if (count($json['messages'])) {
            foreach ($json['messages'] as $message) {
                if (array_key_exists('user', $message)) {
                    if ($message['user'] == $this->user['slack_id']) {
                        if (!preg_match('/^<@.{9}>:\ /', $message['text'])) {
                            $time = explode('.', $message['ts']);
                            $messageDateTime = \DateTime::createFromFormat('U', $time[0], new \DateTimeZone('GMT+0'));
                            $messageDateTime->setTimeZone(new \DateTimeZone('America/Sao_Paulo'));
                            $messages[$messageDateTime->format('l')][] = '- '.$message['text'];
                        }
                    }
                }
            }
        }

        return $messages;
    }

    /**
     * @param array $dones
     */
    private function postMessages(array $dones)
    {
        $text = '';
        
        $header = '*<@'.$this->user['slack_id'].'> recent dones:*';

        foreach ($dones as $day => $dones) {
            if (count($dones)) {
                $text .= "\n\n*" . $day . ":*\n";
                $messages = join("\n", array_reverse($dones));
                $text .= $messages;
            }
        }

        if (empty($text)) {
            $text = '- This user has no *done*.';
        }

        die($header.$text);
    }
}

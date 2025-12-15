<?php

namespace App\Controllers;

use App\Core\ServiceResponse;
use App\Models\Services\EmbassyService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class EmbassyController extends BaseController
{
    private EmbassyService $embassyService;

    public function __construct(EmbassyService $embassyService)
    {
        $this->embassyService = $embassyService;
    }

    public function index(Request $request, Response $response): Response
    {
        $userId = $this->getUserId($request);
        $data = $this->embassyService->getEmbassyData($userId);

        return $this->render($response, 'embassy/index.php', $data);
    }

    public function activate(Request $request, Response $response): Response
    {
        $userId = $this->getUserId($request);
        $body = $request->getParsedBody();
        $edictKey = $body['edict_key'] ?? '';

        $result = $this->embassyService->activateEdict($userId, $edictKey);

        if ($result->success) {
            $this->flashSuccess($result->message);
        } else {
            $this->flashError($result->message);
        }

        return $response->withHeader('Location', '/embassy')->withStatus(302);
    }

    public function revoke(Request $request, Response $response): Response
    {
        $userId = $this->getUserId($request);
        $body = $request->getParsedBody();
        $edictKey = $body['edict_key'] ?? '';

        $result = $this->embassyService->revokeEdict($userId, $edictKey);

        if ($result->success) {
            $this->flashSuccess($result->message);
        } else {
            $this->flashError($result->message);
        }

        return $response->withHeader('Location', '/embassy')->withStatus(302);
    }
}

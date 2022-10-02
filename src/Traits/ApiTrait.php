<?php

namespace Wbcodes\Core\Helpers\Traits;

use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;

trait ApiTrait
{
    /**
     * @return array
     */
    public function apiAcceptSuccessCodes(): array
    {
        return [
            200, 201, 202
        ];
    }

    /**
     * @param  null  $data
     * @param  int  $code
     * @param  string|null  $title
     * @return ResponseFactory|Response
     */
    public function apiResponse($data = null, int $code = 200, string $title = null)
    {
        if (!$title) {
            $title = $this->getDefaultResponseTitle($code);
        }

        $array = [
            'status' => in_array($code, $this->apiAcceptSuccessCodes()),
            'code'   => $code,
            'title'  => $title,
            'data'   => $data,
        ];
        if (is_countable($data)) {
            $array['count'] = sizeof($data);
        }
        return response($array, $code);
    }

    /**
     * @param  null  $data
     * @param  string|null  $title
     * @return ResponseFactory|Response
     */
    public function apiSuccessResponse($data = null, string $title = null)
    {
        return $this->apiResponse($data, 200, $title);
    }

    /**
     * @param  null  $errors
     * @return ResponseFactory|Response
     */
    public function apiValidationError($errors = null)
    {
        return $this->apiResponse($errors, 422, "Validation Errors.");
    }

    /**
     * @param $data
     * @param  int  $code
     * @return ResponseFactory|Response
     */
    public function createdResponse($data, int $code = 201)
    {
        return $this->apiResponse($data, $code);
    }

    /**
     * @return ResponseFactory|Response
     */
    public function deleteResponse($code = 201)
    {
        return $this->apiResponse(true, $code);
    }

    /**
     * @return ResponseFactory|Response
     */
    public function notFoundResponse()
    {
        return $this->apiResponse(null, 404, 'not found !');
    }

    /**
     * @return ResponseFactory|Response
     */
    public function unauthorizedError()
    {
        return $this->apiResponse(null, 403, 'unauthorized user.');
    }

    /**
     * @return ResponseFactory|Response
     */
    public function unKnowError()
    {
        return $this->apiResponse(null, 520, 'Unknown error');
    }

    /**
     * @return ResponseFactory|Response
     */
    public function forbiddenResponse()
    {
        return $this->apiResponse(null, 403, 'forbidden');
    }

    /**
     * @param $request
     * @param $array
     * @return ResponseFactory|Response
     */
    public function apiValidation($request, $array)
    {
        $validate = Validator::make($request->all(), $array);
        if ($validate->fails()) {
            return $this->apiResponse($validate->errors(), 422);
        }
    }

    /**
     * @param $code
     * @return string|null
     */
    public function getDefaultResponseTitle($code)
    {
        switch ($code) {
            case"200":
                $title = "Ok";
                break;
            case"201":
                $title = "Created";
                break;
            case"202":
                $title = "Accepted";
                break;
            case"422":
                $title = "Validation Error";
                break;
            case"401":
                $title = "Unauthorized";
                break;
            case"403":
                $title = "Forbidden";
                break;
            case"404":
                $title = "Not Found!";
                break;
            case"500":
                $title = "Internal Server Error";
                break;
            default:
                $title = null;
                break;
        }
        return $title;
    }
}
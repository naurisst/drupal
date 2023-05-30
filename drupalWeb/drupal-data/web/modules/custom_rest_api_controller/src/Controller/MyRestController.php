<?php
namespace Drupal\custom_rest_api_controller\Controller;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\node\Entity\Node;

class MyRestController extends ControllerBase
{
  public function postData(Request $req)
  {
    //decode the json data
    $data = Json::decode($req->getContent());
    //check if the name property is present or not
    if (isset($data['sensor']) && isset($data['value'])) {
      $node = Node::create(
        [
          'type' => 'sensor_value',
          'title' => [
            'value' => $data['sensor'],
          ],
          'field_value' => [
            'value' => $data['value'],
          ],
          'field_sensor' => intval($data['sensor'])
        ]
      );

      $node->enforceIsNew();
      $node->save();
    }
    $response = new JsonResponse(json_encode([
      "date" => date("Y:m:d H:i:s"),
      "input" => $data
    ]));
    //set header to be application/json
    $response->headers->set('Content-Type', 'application/json'); //return data
    return $response;

  }
}

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

    //create sensor value content
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

    //get sensor type
    // 1 - co2
    // 2 - temp
    // 3 - hum
    $database = \Drupal::database();
    $query = $database->query("SELECT * FROM node__field_sensor_type where entity_id=".$data['sensor']);
    $result = $query->fetchAll();
    $sensorType = $result[0]->field_sensor_type_target_id;

    //get alert rule
    $query = $database->query("SELECT * FROM node__field_sensor_type_to_check where field_sensor_type_to_check_target_id=".$sensorType);
    $result = $query->fetchAll();
    $ruleId = $result[0]->entity_id;

    // get min value
    $query = $database->query("SELECT * FROM node__field_min_value where entity_id=".$ruleId);
    $result = $query->fetchAll();
    $minValue = $result[0]->field_min_value_value;

    // get max value
    $query = $database->query("SELECT * FROM node__field_max_value where entity_id=".$ruleId);
    $result = $query->fetchAll();
    $maxValue = $result[0]->field_max_value_value;

    //create alert content
    if ($data['value']<$minValue || $data['value']>$maxValue) {

      $condition = '';
      if($data['value']>$maxValue)
        $condition = 'too high';
      else if ($data['value']<$minValue)
        $condition = 'too low';

      $type= '';
      if($sensorType == 1)
        $type = 'CO2';
      else if ($sensorType == 2)
        $type = 'Temperature';
      else if ($sensorType == 3)
        $type = 'Humidity';

      $alertText = $type . ' sensors ' . $data['sensor'] . ' measurement is ' . $condition;

      $node = Node::create(
        [
          'type' => 'alert',
          'title' => [
            'value' => date("Y:m:d H:i:s") . '-' . $data['sensor'],
          ],
          'field_alert_text' => [
            'value' => $alertText,
          ],
          'field_measurement' => [
            'value' => $data['value'],
          ],
          'field_target_sensor' => intval($data['sensor'])
        ]
      );

      $node->enforceIsNew();
      $node->save();
    }

    //create response
    $response = new JsonResponse(json_encode([
      "date" => date("Y:m:d H:i:s"),
      "response" => 'measurement and alert created'
    ]));
    //set header to be application/json
    $response->headers->set('Content-Type', 'application/json'); //return data
    return $response;

  }
}

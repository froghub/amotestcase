<?php
require_once 'logger.php';
require_once 'api.php';

class HookProcess
{

    private $api;

    public function __construct()
    {
        $this->api = new Api();
    }

    function run()
    {
        $dataRaw = $_POST;
        unset($dataRaw['account']);

        $entity = array_keys($dataRaw)[0] ?? '';
        if (!in_array($entity, ['leads', 'contacts'])) {
            Logger::log("Данный хук не обрабатывается (Хук: $entity)");
            return;
        }

        $action = array_keys($dataRaw[$entity])[0] ?? '';
        if (!in_array($action, ['add', 'update'])) {
            Logger::log("Данное действие не обрабатывается (Действие: $action)");
            return;
        }

        $this->handleHook($entity, $action, $dataRaw);
    }


    private function handleHook($entity, $action, $data)
    {
        $items = $data[$entity][$action];
        foreach ($items as $item) {
            switch ($action) {
                case 'add': {
                        $responsible =  $this->getResponsible($item['responsible_user_id']);
                        if (empty($responsible)) {
                            Logger::log('Action processing error . Response: ' . var_export($item, true) . PHP_EOL);
                            return;
                        }
                        $noteText = "Название " . ($entity == 'leads' ? 'сделки' : 'контакта') . ": " . $item['name'] . ", Ответственный: " . $responsible['name'] . ", Создано: " . date('H:i:s d.m.Y', $item['created_at']);
                        $this->addNote($entity, $item['id'], $item['created_user_id'], $noteText);
                        break;
                    }
                case 'update': {
                        Logger::log('Update entity '.$entity.', ID:' . $item['id']);
                        break; //добавление note тоже вызывает update. Для начала отфильтровать
                        $noteText = "Изменения полей: ";

                        //Запросить события.в хуке нет инфы по изменениям
                        if(isset($item['custom_fields'])){
                            foreach ($item['custom_fields'] as $field) {
                                $noteText .= $field['name'] . ' присвоено ' . $field['values'][0]['value'] . ', ';
                            }
                        }

                        $noteText .=  "Изменено: " . date('H:i:s d.m.Y', $item['updated_at']);;
                        $this->addNote($entity, $item['id'], $item['created_user_id'], $noteText);
                        break;
                    }
            }
        }
    }

    // GET users/{id}
    private function getResponsible($id)
    {
        $data = $this->api->get("api/v4/users/$id");
        $data = json_decode($data, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            Logger::log('Responsible get error. Response: ' . var_export($data, true) . PHP_EOL);
            $data = [];
        }
        return $data;
    }

    // POST  {справочник}/{el_id}/notes
    private function addNote($entity, $entityId, $creatorId, $text)
    {
        $note = [
            'created_by' => $creatorId,
            'note_type' => 'common',
            'params' => [
                'text' => $text
            ]
        ];
        $this->api->post('api/v4/' . $entity . '/' . $entityId . '/notes', [$note]);
    }
}

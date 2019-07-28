<?php

namespace Kanboard\Plugin\SendEmailCreator\Action;

use Kanboard\Model\TaskModel;
use Kanboard\Action\Base;

/**
 * Email a task notification of impending due date 
 */
class SendTaskEmailStart extends Base
{
    /**
     * Get automatic action description
     *
     * @access public
     * @return string
     */
    public function getDescription()
    {
        return t('Send email notification of start data occur task');
    }
    /**
     * Get the list of compatible events
     *
     * @access public
     * @return array
     */
    public function getCompatibleEvents()
    {
        return array(
            TaskModel::EVENT_DAILY_CRONJOB,
        );
    }
    /**
     * Get the required parameter for the action (defined by the user)
     *
     * @access public
     * @return array
     */
    public function getActionRequiredParameters()
    {
        return array(
            'send_to' => array('assignee' => t('Send to Assignee'), 'creator' => t('Send to Creator'), 'both' => t('Send to Both')),
        );
    }
    /**
     * Get the required parameter for the event
     *
     * @access public
     * @return string[]
     */
    public function getEventRequiredParameters()
    {
        return array('tasks');
    }
    /**
     * Check if the event data meet the action condition
     *
     * @access public
     * @param  array   $data   Event data dictionary
     * @return bool
     */
    public function hasRequiredCondition(array $data)
    {
        return count($data['tasks']) > 0;
    }

    public function doAction(array $data)
    {
        $results = array();

        if ($this->getParam('send_to') !== null) {
            $send_to = $this->getParam('send_to');
        } else {
            $send_to = 'both';
        }

        foreach ($data['tasks'] as $task) {
            if ($task['date_started'] - 86400 < time() && $task['date_started'] + 86400 > time()) {
                if ($send_to == 'assignee' || $send_to == 'both') {
                    $user = $this->userModel->getById($task['owner_id']);
                    if (!empty($user['email'])) {
                        $results[] = $this->sendEmail($task['id'], $user);
                        $this->taskMetadataModel->save($task['id'], ['task_last_emailed_toassignee' => time()]);
                    }
                }
                if ($send_to == 'creator' || $send_to == 'both') {
                    $user = $this->userModel->getById($task['creator_id']);
                    if (!empty($user['email'])) {
                        $results[] = $this->sendEmail($task['id'], $user);
                        $this->taskMetadataModel->save($task['id'], ['task_last_emailed_tocreator' => time()]);
                    }
                }
            }
        }

        return in_array(true, $results, true);
    }
    /**
     * Send email
     *
     * @access private
     * @param  integer $task_id
     * @param  array   $user
     * @return boolean
     */
    private function sendEmail($task_id, array $user)
    {
        $task = $this->taskFinderModel->getDetails($task_id);
        $subtasks = $this->subtaskModel->getAll($task['id']);
        $commentSortingDirection = $this->userMetadataCacheDecorator->get(UserMetadataModel::KEY_COMMENT_SORTING_DIRECTION, 'ASC');
        $this->emailClient->send(
            $user['email'],
            $user['name'] ?: $user['username'],
            '[Kanboard] ' . $task['title'],
            $this->template->render('task/show', array(
                'task' => $task,
                'project' => $this->projectModel->getById($task['project_id']),
                'files' => $this->taskFileModel->getAllDocuments($task['id']),
                'images' => $this->taskFileModel->getAllImages($task['id']),
                'comments' => $this->commentModel->getAll($task['id'], $commentSortingDirection),
                'subtasks' => $subtasks,
                'internal_links' => $this->taskLinkModel->getAllGroupedByLabel($task['id']),
                'external_links' => $this->taskExternalLinkModel->getAll($task['id']),
                'link_label_list' => $this->linkModel->getList(0, false),
                'tags' => $this->taskTagModel->getList($task['id']),
            ))
        );
        return true;
    }
}

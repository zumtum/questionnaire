<?php
/**
 * @file
 * Contains \Drupal\questionnaire_lists\Plugin\Block\QuestionnairesEmployeeBlock.
 */

namespace Drupal\questionnaire_lists\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\user\Entity\User;

/**
 * Provides a 'QuestionnairesEmployee' Block
 *
 * @Block(
 *   id = "questionnaire_lists_block_for_employee",
 *   admin_label = @Translation("Questionnaire list for employee"),
 * )
 */
class QuestionnairesEmployeeBlock extends BlockBase {

    public function getListForEmployee() {
        $current_user_id = \Drupal::currentUser()->id();

        $sids_current_user = $this->getAllQuestionnairesIDForUser($current_user_id);

        $webformSubmissions = \Drupal::service('entity.manager')->getStorage('webform_submission')->loadMultiple($sids_current_user);

        return $this->createListItemFromWebformSubmission($webformSubmissions);
    }

    // get teamleads name by id
    public function getTimlId($emloyee_id) {
        $ids = \Drupal::entityQuery('user')
            ->condition('status', 1)
            ->condition('roles', 'team_lider')
            ->execute();
        $teamleads = User::loadMultiple($ids);

        foreach ($teamleads as $teamlead) {
            $selected_employees = $teamlead->get('field_select_users')->getValue();
            foreach ($selected_employees as $selected_employee) {

                if (in_array($emloyee_id, $selected_employee)) {
                    return $teamlead;
                }
            }

        }

        return false;
    }

    // creating a list of submisiions for employee.
    private function createListItemFromWebformSubmission($submissions) {
        $webformSubmissionsList = [];

        foreach ($submissions as $webformSubmission) {

            $innerDataWebformSubmission = $webformSubmission->getData();

            $webformSubmissionsList[] = array(
                'sid' => $webformSubmission->id(),
                'label' => $webformSubmission->label(),
                'serial' => $webformSubmission->serial(),
                'uuid' => $webformSubmission->uuid(),
                'is_draft' => $webformSubmission->isDraft() ? true : false,
                'submitted_by_id' => $webformSubmission->getOwnerId(),
                'created' => $webformSubmission->getCreatedTime(),
                'changed' => $webformSubmission->getChangedTime(),
                'sticky' => $webformSubmission->isSticky() ? true : false,
                'locked' => $webformSubmission->isLocked() ? true : false,
                'id_employee' => $innerDataWebformSubmission['id_sotrudnika'],
                'id_teamlead' => $innerDataWebformSubmission['id_timlida'],
                'name_teamlead' => $innerDataWebformSubmission['name_timlida'],
                'token' => $webformSubmission->getToken(),
                'update_url' => $webformSubmission->getTokenUrl()->toString(),
                'view_url' => str_replace('form', 'webform', $webformSubmission->getSourceUrl()->toString())
            );

        }

        return $webformSubmissionsList;
    }

    // db query get list questionnaire for current employee
    private function getAllQuestionnairesIDForUser($user_id) {
        $all_submissions = [];

        $submission_query = \Drupal::database()->select('webform_submission', 'ws');
        $submission_query->fields('ws', ['sid']);

        $condition_or = new \Drupal\Core\Database\Query\Condition('OR');
        $condition_or->condition('ws.uid', $user_id);
        $condition_or->condition('wsd.value', $user_id);

        $submission_query->join('webform_submission_data', 'wsd', 'wsd.sid = ws.sid');

        $submission_query->condition($condition_or);
        $submission_query->condition('wsd.name', 'team_entity_sotrudnik');

        $submission_query->orderBy('ws.created', 'DESC');

        $all_submissions = $submission_query->execute()->fetchCol();

        return $all_submissions;
    }

    public function build() {

        return array(
            '#theme' => 'questionnaires-employee-display',
            '#teamlead' => $this->getTimlid(\Drupal::currentUser()->id()),
            '#submissions' => $this->getListForEmployee(),
            '#cache' => array(
                'max-age' => 0,
            ),
        );
    }

}
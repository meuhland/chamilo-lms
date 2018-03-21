<?php
/* For licensing terms, see /license.txt */

use Chamilo\CourseBundle\Entity\CSurvey;
use Chamilo\CourseBundle\Entity\CSurveyInvitation;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;

$cidReset = true;

require_once __DIR__.'/../inc/global.inc.php';

api_block_anonymous_users(true);

$em = Database::getManager();

$currentUser = api_get_user_entity(api_get_user_id());
$avatarPath = UserManager::getUserPicture($currentUser->getId());
$pending = SurveyUtil::getUserPendingInvitations($currentUser->getId());

$surveysData = [];

foreach ($pending as $i => $item) {
    if (is_a($item, 'Chamilo\CourseBundle\Entity\CSurveyInvitation')) {
        continue;
    }

    /** @var CSurvey $survey */
    $survey = $item;
    /** @var CSurveyInvitation invitation */
    $invitation = $pending[$i + 1];
    /** @var Course $course */
    $course = $em->find('ChamiloCoreBundle:Course', $survey->getCId());
    /** @var Session $session */
    $session = $em->find('ChamiloCoreBundle:Session', $survey->getSessionId());

    $surveysData[$survey->getSurveyId()] = [
        'title' => $survey->getTitle(),
        'invitation_code' => $invitation->getInvitationCode(),
        'avail_from' => $survey->getAvailFrom(),
        'avail_till' => $survey->getAvailTill(),
        'course' => $course
            ? ['id' => $course->getId(), 'title' => $course->getTitle(), 'code' => $course->getCode()]
            : null,
        'session' => $session
            ? ['id' => $session->getId(), 'name' => $session->getName()]
            : null
    ];
}

$toolName = get_lang('PendingSurveys');

$template = new Template($toolName);
$template->assign('user', $currentUser);
$template->assign('user_avatar', $avatarPath);
$template->assign('surveys', $surveysData);
$layout = $template->get_template('survey/pending.tpl');
$content = $template->fetch($layout);
$template->assign('header', $toolName);
$template->assign('content', $content);
$template->display_one_col_template();

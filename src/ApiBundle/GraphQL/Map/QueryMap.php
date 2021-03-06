<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\ApiBundle\GraphQL\Map;

use Chamilo\ApiBundle\GraphQL\ApiGraphQLTrait;
use Chamilo\ApiBundle\GraphQL\Resolver\ToolDescriptionResolver;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Message;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\SessionCategory;
use Chamilo\CoreBundle\Security\Authorization\Voter\CourseVoter;
use Chamilo\CourseBundle\Entity\CForumCategory;
use Chamilo\CourseBundle\Entity\CNotebook;
use Chamilo\CourseBundle\Entity\CTool;
use Chamilo\UserBundle\Entity\User;
use GraphQL\Type\Definition\ResolveInfo;
use Overblog\GraphQLBundle\Definition\Argument;
use Overblog\GraphQLBundle\Error\UserError;
use Overblog\GraphQLBundle\Resolver\ResolverMap;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

/**
 * Class QueryMap.
 *
 * @package Chamilo\ApiBundle\GraphQL\Map
 */
class QueryMap extends ResolverMap implements ContainerAwareInterface
{
    use ApiGraphQLTrait;

    /**
     * @return array
     */
    protected function map()
    {
        return [
            'Query' => [
                self::RESOLVE_FIELD => function ($value, Argument $args, \ArrayObject $context, ResolveInfo $info) {
                    $context->offsetSet('course', null);
                    $context->offsetSet('session', null);

                    $method = 'resolve'.ucfirst($info->fieldName);

                    return $this->$method($args, $context);
                },
            ],
            'User' => [
                self::RESOLVE_FIELD => function (
                    User $user,
                    Argument $args,
                    \ArrayObject $context,
                    ResolveInfo $info
                ) {
                    $context->offsetSet('user', $user);
                    $resolver = $this->container->get('chamilo_api.graphql.resolver.user');

                    return $this->resolveField($info->fieldName, $user, $resolver, $args, $context);
                },
            ],
            'UserMessage' => [
                self::RESOLVE_FIELD => function (
                    Message $message,
                    Argument $args,
                    \ArrayObject $context,
                    ResolveInfo $info
                ) {
                    if ('sender' === $info->fieldName) {
                        return $message->getUserSender();
                    }

                    if ('hasAttachments' === $info->fieldName) {
                        return $message->getAttachments()->count() > 0;
                    }

                    return $this->resolveField($info->fieldName, $message);
                },
            ],
            'Course' => [
                self::RESOLVE_FIELD => function (
                    Course $course,
                    Argument $args,
                    \ArrayObject $context,
                    ResolveInfo $info
                ) {
                    $context->offsetSet('course', $course);
                    $resolver = $this->container->get('chamilo_api.graphql.resolver.course');

                    return $this->resolveField($info->fieldName, $course, $resolver, $args, $context);
                },
            ],
            'ToolDescription' => [
                self::RESOLVE_FIELD => function (
                    CTool $tool,
                    Argument $args,
                    \ArrayObject $context,
                    ResolveInfo $info
                ) {
                    if ('descriptions' === $info->fieldName) {
                        $resolver = $this->container->get('chamilo_api.graphql.resolver.course');

                        return $resolver->getDescriptions($tool, $context);
                    }

                    return $this->resolveField($info->fieldName, $tool);
                },
            ],
            //'CourseDescription' => [],
            'ToolAnnouncements' => [
                self::RESOLVE_FIELD => function (
                    CTool $tool,
                    Argument $args,
                    \ArrayObject $context,
                    ResolveInfo $info
                ) {
                    $resolver = $this->container->get('chamilo_api.graphql.resolver.course');

                    if ('announcements' === $info->fieldName) {
                        return $resolver->getAnnouncements($tool, $context);
                    }

                    if ('announcement' === $info->fieldName) {
                        return $resolver->getAnnouncement($args['id'], $context);
                    }

                    return $this->resolveField($info->fieldName, $tool);
                },
            ],
            'CourseAnnouncement' => [
                'content' => function (\stdClass $announcement, Argument $args, \ArrayObject $context) {
                    /** @var User $reader */
                    $reader = $context->offsetGet('user');
                    /** @var Course $course */
                    $course = $context->offsetGet('course');
                    /** @var Session $session */
                    $session = $context->offsetGet('session');

                    return \AnnouncementManager::parseContent(
                        $reader->getId(),
                        $announcement->content,
                        $course->getCode(),
                        $session ? $session->getId() : 0
                    );
                },
            ],
            'ToolNotebook' => [
                self::RESOLVE_FIELD => function (
                    CTool $tool,
                    Argument $args,
                    \ArrayObject $context,
                    ResolveInfo $info
                ) {
                    if ('notes' === $info->fieldName) {
                        $resolver = $this->container->get('chamilo_api.graphql.resolver.course');

                        return $resolver->getNotes($context);
                    }

                    return $this->resolveField($info->fieldName, $tool);
                },
            ],
            'CourseNote' => [
                'id' => function (CNotebook $note) {
                    return $note->getIid();
                },
            ],
            'ToolForums' => [
                self::RESOLVE_FIELD => function (
                    CTool $tool,
                    Argument $args,
                    \ArrayObject $context,
                    ResolveInfo $info
                ) {
                    if ('categories' === $info->fieldName) {
                        $resolver = $this->container->get('chamilo_api.graphql.resolver.course');

                        return $resolver->getForumCategories($context);
                    }

                    return $this->resolveField($info->fieldName, $tool);
                },
            ],
            'CourseForumCategory' => [
                'id' => function (CForumCategory $category) {
                    return $category->getIid();
                },
                'title' => function (CForumCategory $category) {
                    return $category->getCatTitle();
                },
                'comment' => function (CForumCategory $category) {
                    return $category->getCatComment();
                },
            ],
            'Session' => [
                self::RESOLVE_FIELD => function (
                    Session $session,
                    Argument $args,
                    \ArrayObject $context,
                    ResolveInfo $info
                ) {
                    $context->offsetSet('session', $session);
                    $resolver = $this->container->get('chamilo_api.graphql.resolver.session');

                    return $this->resolveField($info->fieldName, $session, $resolver, $args, $context);
                },
            ],
            'SessionCategory' => [
                self::RESOLVE_FIELD => function (
                    SessionCategory $category,
                    Argument $args,
                    \ArrayObject $context,
                    ResolveInfo $info
                ) {
                    if ('startDate' === $info->fieldName) {
                        return $category->getDateStart();
                    }

                    if ('endDate' === $info->fieldName) {
                        return $category->getDateEnd();
                    }

                    return $this->resolveField($info->fieldName, $category);
                },
            ],
        ];
    }

    /**
     * @param string            $fieldName
     * @param object            $object
     * @param object|null       $resolver
     * @param Argument|null     $args
     * @param \ArrayObject|null $context
     *
     * @return mixed
     */
    private function resolveField(
        $fieldName,
        $object,
        $resolver = null,
        Argument $args = null,
        \ArrayObject $context = null
    ) {
        $method = 'get'.ucfirst($fieldName);

        if ($resolver && $args && $context && method_exists($resolver, $method)) {
            return $resolver->$method($object, $args, $context);
        }

        return $object->$method();
    }

    /**
     * @return User
     */
    protected function resolveViewer()
    {
        $this->checkAuthorization();

        return $this->getCurrentUser();
    }

    /**
     * @param Argument $args
     *
     * @return Course
     */
    protected function resolveCourse(Argument $args)
    {
        $this->checkAuthorization();

        $id = (int) $args['id'];

        $courseRepo = $this->em->getRepository('ChamiloCoreBundle:Course');
        $course = $courseRepo->find($id);

        if (!$course) {
            throw new UserError($this->translator->trans('Course not found.'));
        }

        $checker = $this->container->get('security.authorization_checker');

        if (false === $checker->isGranted(CourseVoter::VIEW, $course)) {
            throw new UserError($this->translator->trans('Not allowed'));
        }

        return $course;
    }

    /**
     * @param Argument $args
     *
     * @return Session
     */
    protected function resolveSession(Argument $args)
    {
        $this->checkAuthorization();

        $sessionRepo = $this->em->getRepository('ChamiloCoreBundle:Session');
        /** @var Session $session */
        $session = $sessionRepo->find($args['id']);

        if (!$session) {
            throw new UserError($this->translator->trans('Session not found.'));
        }

        return $session;
    }

    /**
     * @param Argument $args
     *
     * @return SessionCategory
     */
    protected function resolveSessionCategory(Argument $args)
    {
        $this->checkAuthorization();

        $repo = $this->em->getRepository('ChamiloCoreBundle:SessionCategory');
        /** @var SessionCategory $category */
        $category = $repo->find($args['id']);

        if (!$category) {
            throw new UserError($this->translator->trans('Session category not found.'));
        }

        return $category;
    }
}

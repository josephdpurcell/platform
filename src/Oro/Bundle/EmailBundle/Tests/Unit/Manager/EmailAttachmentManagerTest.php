<?php

namespace Oro\src\Oro\Bundle\EmailBundle\Tests\Unit\Manager;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManager;

use Gaufrette\Filesystem;

use Knp\Bundle\GaufretteBundle\FilesystemMap;

use Oro\Bundle\SecurityBundle\SecurityFacade;
use Symfony\Component\HttpKernel\KernelInterface;

use Oro\Bundle\AttachmentBundle\Validator\ConfigFileValidator;
use Oro\Bundle\EmailBundle\Cache\EmailCacheManager;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailAttachment;
use Oro\Bundle\EmailBundle\Manager\EmailAttachmentManager;
use Oro\Bundle\EmailBundle\Tests\Unit\Fixtures\Entity\SomeEntity;
use Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink;
use Oro\Bundle\EmailBundle\Provider\EmailActivityListProvider;
use Oro\Bundle\AttachmentBundle\Entity\Attachment;

/**
 * Class EmailAttachmentManagerTest
 *
 * @package Oro\src\Oro\Bundle\EmailBundle\Tests\Unit\Manager
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class EmailAttachmentManagerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var EmailAttachmentManager
     */
    protected $emailAttachmentManager;

    /**
     * @var EmailCacheManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $emailCacheManager;

    /**
     * @var Filesystem|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $filesystem;

    /**
     * @var Registry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $registry;

    /**
     * @var ConfigFileValidator|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $configFileValidator;

    /**
     * @var KernelInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $kernel;

    /**
     * @var ServiceLink|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $securityFacadeLink;

    /**
     * @var SecurityFacade|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $securityFacade;

    /**
     * @var ObjectManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $om;

    /**
     * @var EmailActivityListProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $activityListProvider;

    /**
     * @var EntityManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $em;

    /**
     * @var Attachment|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $attachment;

    protected function setUp()
    {
        $this->emailCacheManager = $this->getMockBuilder('Oro\Bundle\EmailBundle\Cache\EmailCacheManager')
            ->disableOriginalConstructor()
            ->getMock();

        $filesystemMap = $this->getMockBuilder('Knp\Bundle\GaufretteBundle\FilesystemMap')
            ->disableOriginalConstructor()
            ->getMock();

        $this->filesystem = $this->getMockBuilder('Gaufrette\Filesystem')
            ->disableOriginalConstructor()
            ->getMock();

        $filesystemMap->expects($this->once())
            ->method('get')
            ->with('attachments')
            ->will($this->returnValue($this->filesystem));

        $this->registry = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->getMock();

        $this->configFileValidator = $this->getMockBuilder('Oro\Bundle\AttachmentBundle\Validator\ConfigFileValidator')
            ->disableOriginalConstructor()
            ->getMock();

        $this->kernel = $this->getMock('Symfony\Component\HttpKernel\KernelInterface');
        $this->kernel->expects($this->once())
            ->method('getRootDir')
            ->willReturn('');

        $this->om = $this->getMockBuilder('Doctrine\Common\Persistence\ObjectManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->registry->expects($this->any())
            ->method('getManager')
            ->will($this->returnValue($this->om));

        $this->securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->setMethods(['getLoggedUser'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->securityFacadeLink = $this
            ->getMockBuilder('Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink')
            ->setMethods(['getService'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->securityFacadeLink->expects($this->any())
            ->method('getService')
            ->will($this->returnValue($this->securityFacade));

        $this->activityListProvider = $this
            ->getMockBuilder('Oro\Bundle\EmailBundle\Provider\EmailActivityListProvider')
            ->setMethods(['getTargetEntities'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->attachment = $this->getMockBuilder('Oro\Bundle\AttachmentBundle\Entity\Attachment')
            ->setMethods(['supportTarget', 'setFile'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->emailAttachmentManager = $this->getMockBuilder('Oro\Bundle\EmailBundle\Manager\EmailAttachmentManager')
            ->setMethods(['getAttachmentFullPath', 'getNewAttachment'])
            ->setConstructorArgs(
                [
                    $filesystemMap,
                    $this->em,
                    $this->kernel,
                    $this->securityFacadeLink,
                    $this->activityListProvider,
                    $this->configFileValidator
                ]
            )
            ->getMock();

        $this->configFileValidator->expects($this->any())
            ->method('validate')
            ->willReturn($this->getMock('Countable'));

        $this->emailAttachmentManager->expects($this->any())
            ->method('getAttachmentFullPath')
            ->willReturn(__DIR__ . '/../Fixtures/attachment/test.txt');
    }

    public function testLinkEmailAttachmentToTargetEntity()
    {
        $emailAttachment = $this->getEmailAttachment();

        $this->attachment->expects($this->any())
            ->method('supportTarget')
            ->withAnyParameters()
            ->will($this->returnValue(true));

        $this->emailAttachmentManager
            ->method('getNewAttachment')
            ->withAnyParameters()
            ->will($this->returnValue($this->attachment));

        $emailAttachment->expects($this->exactly(1))
            ->method('setFile')
            ->withAnyParameters();

        $this->attachment->expects($this->exactly(1))
            ->method('setFile')
            ->withAnyParameters();

        $this->emailAttachmentManager->linkEmailAttachmentToTargetEntity($emailAttachment, new SomeEntity());
    }

    public function testLinkEmailAttachmentsToTargetEntities()
    {
        $emailAttachment = $this->getEmailAttachment();
        $email = $this->getEmailMock($emailAttachment);

        $this->attachment->expects($this->any())
            ->method('supportTarget')
            ->withAnyParameters()
            ->will($this->returnValue(true));

        $this->emailAttachmentManager
            ->method('getNewAttachment')
            ->withAnyParameters()
            ->will($this->returnValue($this->attachment));

        $this->activityListProvider
            ->method('getTargetEntities')
            ->withAnyParameters()
            ->will($this->returnValue([new SomeEntity()]));

        $emailAttachment->expects($this->exactly(1))
            ->method('setFile')
            ->withAnyParameters();

        $this->attachment->expects($this->exactly(1))
            ->method('setFile')
            ->withAnyParameters();

        $this->emailAttachmentManager->linkEmailAttachmentsToTargetEntities($email);
    }

    protected function getContentMock()
    {
        $content = $this->getMockBuilder('\stdClass')
            ->setMethods(['getContent', 'getContentTransferEncoding'])
            ->getMock();
        $content->expects($this->any())
            ->method('getContent')
            ->will($this->returnValue('content'));
        $content->expects($this->any())
            ->method('getContentTransferEncoding')
            ->will($this->returnValue('base64'));

        return $content;
    }

    protected function getEmailAttachment()
    {
        $emailAttachment = $this->getMockBuilder('Oro\Bundle\EmailBundle\Entity\EmailAttachment')
            ->setMethods(['getContent', 'setFile', 'getFile'])
            ->disableOriginalConstructor()
            ->getMock();
        $emailAttachment->expects($this->any())
            ->method('getContent')
            ->will($this->returnValue($this->getContentMock()));
        $emailAttachment->expects($this->any())
            ->method('getFile')
            ->will($this->returnValue(true));

        return $emailAttachment;
    }

    /**
     * @param EmailAttachment $emailAttachment
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getEmailMock(EmailAttachment $emailAttachment)
    {
        $email = $this->getMockBuilder('Oro\Bundle\EmailBundle\Entity\Email')
            ->setMethods(['getEmailBody', 'getAttachments'])
            ->disableOriginalConstructor()
            ->getMock();
        $email->expects($this->any())
            ->method('getEmailBody')
            ->will($this->returnValue($email));
        $email->expects($this->any())
            ->method('getAttachments')
            ->will($this->returnValue([$emailAttachment]));

        return $email;
    }
}
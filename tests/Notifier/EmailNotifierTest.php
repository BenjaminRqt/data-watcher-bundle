<?php declare(strict_types=1);

namespace BenjaminRqt\DataWatcherBundle\Tests\Notifier;

use BenjaminRqt\DataWatcherBundle\Check\CheckInterface;
use BenjaminRqt\DataWatcherBundle\Check\CheckResult;
use BenjaminRqt\DataWatcherBundle\Notifier\EmailNotifier;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Twig\Environment;

class EmailNotifierTest extends TestCase
{
    private MailerInterface $mailer;
    private Environment $twig;
    private EmailNotifier $notifier;

    protected function setUp(): void
    {
        $this->mailer = $this->createMock(MailerInterface::class);
        $this->twig = $this->createMock(Environment::class);
        $this->notifier = new EmailNotifier(
            $this->mailer,
            $this->twig,
            'from@example.com',
            ['default@example.com'],
            'MyApp'
        );
    }

    public function testNotifyWithCheckRecipients(): void
    {
        $check = $this->createMock(CheckInterface::class);
        $check->method('getName')->willReturn('test-check');
        $check->method('getRecipients')->willReturn(['check@example.com']);

        $result = CheckResult::anomalies([['id' => 1]], 'Anomalie');

        $this->twig->expects($this->once())
            ->method('render')
            ->with('@DataWatcher/alert_email.html.twig', $this->anything())
            ->willReturn('<html>Body</html>');

        $this->mailer->expects($this->once())
            ->method('send')
            ->with($this->callback(function (Email $email) {
                return $email->getFrom()[0]->getAddress() === 'from@example.com'
                    && $email->getTo()[0]->getAddress() === 'check@example.com'
                    && str_contains($email->getSubject(), 'test-check');
            }));

        $this->notifier->notify($check, $result);
    }

    public function testNotifyWithDefaultRecipients(): void
    {
        $check = $this->createMock(CheckInterface::class);
        $check->method('getName')->willReturn('test-check');
        $check->method('getRecipients')->willReturn([]); // Vide, utilise par défaut

        $result = CheckResult::anomalies([['id' => 1]], 'Anomalie');

        $this->mailer->expects($this->once())
            ->method('send')
            ->with($this->callback(function (Email $email) {
                return $email->getTo()[0]->getAddress() === 'default@example.com';
            }));

        $this->notifier->notify($check, $result);
    }

    public function testNotifyThrowsExceptionIfNoRecipients(): void
    {
        $notifier = new EmailNotifier($this->mailer, $this->twig, 'from@example.com', [], null);
        
        $check = $this->createMock(CheckInterface::class);
        $check->method('getName')->willReturn('test-check');
        $check->method('getRecipients')->willReturn([]);

        $result = CheckResult::ok();

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('No recipient for check "test-check"');

        $notifier->notify($check, $result);
    }
}

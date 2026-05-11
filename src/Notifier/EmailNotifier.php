<?php declare(strict_types=1);

namespace BenjaminRqt\DataWatcherBundle\Notifier;

use BenjaminRqt\DataWatcherBundle\Check\CheckInterface;
use BenjaminRqt\DataWatcherBundle\Check\CheckResult;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Twig\Environment;

class EmailNotifier
{
    public function __construct(
        private MailerInterface $mailer,
        private Environment $twig,
        private string $from,
        /** @var string[] */
        private array $defaultRecipients,
        private ?string $appName,
    ) {
    }

    public function notify(CheckInterface $check, CheckResult $result): void
    {
        $recipients = $check->getRecipients() ?: $this->defaultRecipients;

        if (empty($recipients)) {
            throw new \LogicException(sprintf(
                'No recipient for check "%s". Configure "data_watcher.recipients" or implement getRecipients().',
                $check->getName(),
            ));
        }

        $subject = sprintf(
            '[DataWatcher]%s ⚠️ %s — %d anomaly(ies)',
            $this->appName ? '[' . $this->appName . ']' : '',
            $check->getName(),
            $result->count,
        );

        $email = (new Email())
            ->from($this->from)
            ->to(...$recipients)
            ->subject($subject)
            ->html($this->twig->render('@DataWatcher/alert_email.html.twig', [
                'check'  => $check,
                'result' => $result,
                'appName' => $this->appName,
            ]));

        $this->mailer->send($email);
    }
}

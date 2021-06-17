<?php

namespace Voryx\ThruwayBundle\Command;

use Psr\Container\ContainerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Thruway\Transport\RatchetTransportProvider;

class ThruwayRouterCommand extends \Symfony\Component\Console\Command\Command
{
    public function __construct(
        private ContainerInterface $container,
    ) {
        parent::__construct();
    }

    private function getContainer() {
        return $this->container;
    }

    /**
     * @var \Psr\Log\LoggerInterface $logger
     */
    private $logger;

    /**
     * Called by the Service Container.
     */
    public function setLogger(\Psr\Log\LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('thruway:router:start')
            ->setDescription('Start the default Thruway WAMP router')
            ->setHelp('The <info>%command.name%</info> starts the Thruway WAMP router.');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($this->getContainer()->getParameter('voryx_thruway')['enable_logging'])
        {
            \Thruway\Logging\Logger::set($this->logger);
        }
        else
        {
            \Thruway\Logging\Logger::set(new \Psr\Log\NullLogger());
        }

        try {
            $output->writeln('Making a go at starting the Thruway Router');

            //Configure stuff
            $config = $this->getContainer()->getParameter('voryx_thruway');

            //Get the Router Service
            $server = $this->getContainer()->get('voryx.thruway.server');

            //Trusted provider (bound to loopback and requires no authentication)
            $trustedProvider = new RatchetTransportProvider($config['router']['ip'], $config['router']['trusted_port']);
            $trustedProvider->setTrusted(true);
            $server->addTransportProvider($trustedProvider);

            $server->start();

        } catch (\Exception $e) {
            $this->logger->critical('EXCEPTION:' . $e->getMessage());
            $output->writeln('EXCEPTION:' . $e->getMessage());
        }

        return \Symfony\Component\Console\Command\Command::SUCCESS;
    }
}

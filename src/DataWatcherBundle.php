<?php declare(strict_types=1);

namespace BenjaminRqt\DataWatcherBundle;

use BenjaminRqt\DataWatcherBundle\Check\CheckInterface;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

class DataWatcherBundle extends AbstractBundle implements PrependExtensionInterface
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container
            ->registerForAutoconfiguration(CheckInterface::class)
            ->addTag('data_watcher.check');
    }

    public function prependExtension(ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        if ($builder->hasExtension('twig')) {
            $builder->prependExtensionConfig('twig', [
                'paths' => [
                    dirname(__DIR__) . '/templates/data_watcher' => 'DataWatcher',
                ],
            ]);
        }

        if ($builder->hasExtension('doctrine')) {
            $builder->prependExtensionConfig('doctrine', [
                'orm' => [
                    'mappings' => [
                        'DataWatcherBundle' => [
                            'is_bundle' => false,
                            'type' => 'attribute',
                            'dir' => dirname(__DIR__) . '/src/Entity',
                            'prefix' => 'BenjaminRqt\DataWatcherBundle\Entity',
                            'alias' => 'DataWatcher',
                        ],
                    ],
                ],
            ]);
        }

        if ($builder->hasExtension('doctrine_migrations')) {
            $builder->prependExtensionConfig('doctrine_migrations', [
                'migrations_paths' => [
                    'BenjaminRqt\DataWatcherBundle\Migrations' => dirname(__DIR__) . '/migrations',
                ],
            ]);
        }
    }

    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->rootNode()
            ->children()
            ->scalarNode('from_email')
            ->isRequired()
            ->cannotBeEmpty()
            ->end()

            ->scalarNode('app_name')
            ->defaultNull()
            ->end()

            ->arrayNode('recipients')
            ->isRequired()
            ->requiresAtLeastOneElement()
            ->scalarPrototype()->end()
            ->end()
            ->end();
    }

    /**
     * @param array<mixed> $config
     */
    public function loadExtension(
        array $config,
        ContainerConfigurator $container,
        ContainerBuilder $builder
    ): void {
        $container->import('../config/services.yaml');

        $builder->getDefinition('benjamin_rqt.data_watcher.notifier.email_notifier')
            ->setArgument('$from', $config['from_email'])
            ->setArgument('$appName', $config['app_name'])
            ->setArgument('$defaultRecipients', $config['recipients']);
    }

    public function getPath(): string
    {
        return dirname(__DIR__);
    }

    public function prepend(ContainerBuilder $container)
    {
        // TODO: Implement prepend() method.
    }
}

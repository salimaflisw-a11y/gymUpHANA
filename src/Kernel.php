<?php

namespace App;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    protected function buildContainer(): ContainerBuilder
    {
        foreach (['cache' => $this->getCacheDir(), 'build' => $this->getEffectiveBuildDir()] as $name => $dir) {
            if (!is_dir($dir)) {
                if (!@mkdir($dir, 0o777, true) && !is_dir($dir)) {
                    throw new \RuntimeException(\sprintf('Unable to create the "%s" directory (%s).', $name, $dir));
                }
            }
            $probe = $dir . '/.probe_' . uniqid();
            if (@file_put_contents($probe, '1') === false) {
                if (!is_writable($dir)) {
                    throw new \RuntimeException(\sprintf('Unable to write in the "%s" directory (%s).', $name, $dir));
                }
            } else {
                @unlink($probe);
            }
        }

        $container = $this->getContainerBuilder();
        $container->addObjectResource($this);
        $container->fileExists($this->getBundlesPath());
        $this->prepareContainer($container);
        $this->registerContainerConfiguration($this->getContainerLoader($container));

        return $container;
    }

    /**
     * @return list<string> An array of allowed values for APP_ENV
     */
    private function getAllowedEnvs(): array
    {
        return ['prod', 'dev', 'test'];
    }

    protected function configureContainer(ContainerConfigurator $container): void
    {
        $container->import('../config/{packages}/*.yaml');
        $container->import('../config/{packages}/' . $this->environment . '/*.yaml');
        $container->import('../config/{services}.yaml');
        $container->import('../config/{services}_' . $this->environment . '.yaml');
    }

    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        $routes->import('../config/{routes}/' . $this->environment . '/*.yaml');
        $routes->import('../config/{routes}/*.yaml');
        $routes->import('../config/{routes}.yaml');
    }
}

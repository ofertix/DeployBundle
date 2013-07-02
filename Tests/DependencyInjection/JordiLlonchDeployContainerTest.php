<?php

/**
 * This file is part of the JordiLlonchDeployBundle
 *
 * (c) Jordi Llonch <llonch.jordi@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace JordiLlonch\Bundle\DeployBundle\Tests\DependencyInjection;

use JordiLlonch\Bundle\DeployBundle\DependencyInjection\JordiLlonchDeployExtension;
use JordiLlonch\Bundle\DeployBundle\DependencyInjection\Compiler\DeployersCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\Yaml\Yaml;
use Mockery;
use Symfony\Component\HttpFoundation\Request;

class JordiLlonchDeployContainerTest extends \PHPUnit_Framework_TestCase
{
    protected $container;

    public function setUp()
    {
        $this->container = new ContainerBuilder();
        //load configuration on extension
        $config = Yaml::parse($this->getBundleConfig());
        $extension = new JordiLlonchDeployExtension();
        $extension->load(array($config), $this->container);

        // create a fake test service
        $testDefinition = new Definition('JordiLlonch\Bundle\DeployBundle\Tests\Fixtures\Fakes\TestDeployer');
        $testDefinition->addTag('jordi_llonch_deploy', array('deployer' => 'test'));
        $this->container->setDefinition('deployer_test', $testDefinition);

        //init container with extension
        $this->container->registerExtension($extension);
        $this->container->addCompilerPass(new DeployersCompilerPass());
        $this->container->compile();
    }

    public function testEngineAfterCompilerPass()
    {
        $this->assertTrue($this->container->hasParameter('jordi_llonch_deploy.config'));
        $this->assertTrue($this->container->hasParameter('jordi_llonch_deploy.zones'));
        $this->assertTrue($this->container->has('jordillonch_deployer.engine'));
    }

    public function testEngineLoads()
    {
        $engine = $this->container->get('jordillonch_deployer.engine');
        $this->assertInstanceOf(
            'JordiLlonch\Bundle\DeployBundle\Service\Engine', $engine
        );

        $zones = $engine->getZonesNames();
        $this->assertInternalType('array', $zones);
        $this->assertCount(1, $zones);
        $this->arrayHasKey('test', $zones);
    }

    protected function getBundleConfig()
    {
        return <<<'EOF'
config:
    project: MyProject
    mail_from: iamrobot@me.com
    mail_to:
        - jordi.llonch@me.com
    local_repository_dir: /tmp/deployer_local_repository
    clean_before_days: 7
zones:
    test:
        deployer: test
        environment: prod
        urls:
            - jllonch@testserver1
        checkout_url: 'git@github.com:jordillonch/JordiLlonchDeployBundle.git'
        checkout_branch: master
        repository_dir: /var/www/production/test/deploy
        production_dir: /var/www/production/test/code
EOF;
    }

    public function tearDown()
    {
        parent::tearDown();
        $this->container = null;
    }
}

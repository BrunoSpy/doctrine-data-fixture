<?php
/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the MIT license. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace ZF\Doctrine\DataFixture;

use Zend\ModuleManager\Feature\AutoloaderProviderInterface;
use Zend\ModuleManager\Feature\ConfigProviderInterface;
use Zend\EventManager\EventInterface;
use Zend\ModuleManager\ModuleManager;
use Doctrine\ORM\Tools\Console\ConsoleRunner;
use ZF\Doctrine\DataFixture\Service\FixtureFactory;
use ZF\Doctrine\DataFixture\ServiceManager\ServiceManager as DataFixtureServiceManager;
use Zend\ServiceManager\ServiceManager;
use Zend\Mvc\MvcEvent;
use Zend\ServiceManager\Config;
use Exception;

/**
 * Base module for Doctrine Data Fixture.
 *
 * @license MIT
 * @link    www.doctrine-project.org
 * @author  Martin Shwalbe <martin.shwalbe@gmail.com>
 * @author  Tom Anderson <tom.h.anderson@gmail.com>
 */
class Module implements
    ConfigProviderInterface,
    AutoloaderProviderInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    /**
     * {@inheritDoc}
     */
    public function getAutoloaderConfig()
    {
        return array(
            'Zend\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__ . '/src/',
                ),
            ),
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getServiceConfig()
    {
        return array(
            'factories' => array(
                'ZF\Doctrine\DataFixture\DataFixtureManager' => function (ServiceManager $serviceManager)
                {
                    $config = $serviceManager->get('Config');
                    $request = $serviceManager->get('Request');

                    $objectManagerKey = $request->params()->get(1);
                    $fixtureGroup = $request->params()->get(2);

                    if (! isset($config['doctrine']['fixture'][$objectManagerKey][$fixtureGroup])) {
                        throw new Exception('Fixture group not found: ' . $objectManagerKey . ' ' . $fixtureGroup);
                    }

                    $dataFixtureConfig = new Config($config['doctrine']['fixture'][$objectManagerKey][$fixtureGroup]);

                    $dataFixtureServiceManager = new DataFixtureServiceManager($dataFixtureConfig);
                    $dataFixtureServiceManager->setObjectManager(
                        $serviceManager->get('doctrine.entitymanager.' . $objectManagerKey)
                    );

                    return $dataFixtureServiceManager;
                }
            ),
        );
    }
}

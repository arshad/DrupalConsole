<?php
/**
 * @file
 * Containt Drupal\AppConsole\Command\GeneratorControllerCommand.
 */

namespace Drupal\AppConsole\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\AppConsole\Command\Helper\ServicesTrait;
use Drupal\AppConsole\Command\Helper\ModuleTrait;
use Drupal\AppConsole\Generator\ControllerGenerator;

class GeneratorControllerCommand extends GeneratorCommand
{
  use ModuleTrait;
  use ServicesTrait;

  protected function configure()
  {
    $this
      ->setDefinition(array(
        new InputOption('module','',InputOption::VALUE_REQUIRED, 'The name of the module'),
        new InputOption('class-name','',InputOption::VALUE_OPTIONAL, 'Controller name'),
        new InputOption('services','',InputOption::VALUE_OPTIONAL, 'Load services'),
        new InputOption('routing', '', InputOption::VALUE_NONE, 'Update routing'),
        new InputOption('test', '', InputOption::VALUE_NONE, 'Generate test'),
      ))
      ->setDescription('Generate controller')
      ->setHelp('The <info>generate:controller</info> command helps you generate a new controller.')
      ->setName('generate:controller');
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $dialog = $this->getDialogHelper();
    if ($input->isInteractive()) {
      if (!$dialog->askConfirmation($output, $dialog->getQuestion('Do you confirm generation', 'yes', '?'), true)) {
        $output->writeln('<error>Command aborted</error>');

        return 1;
      }
    }

    $module = $input->getOption('module');
    $class_name = $input->getOption('class-name');
    $test = $input->getOption('test');
    $services = $input->getOption('services');
    $update_routing = $input->getOption('routing');

    // @see use Drupal\AppConsole\Command\Helper\ServicesTrait::buildServices
    $build_services = $this->buildServices($services);

    $this->getGenerator()
      ->generate($module, $class_name, $test, $build_services, $update_routing);

    $errors = '';
    $dialog->writeGeneratorSummary($output, $errors);
  }

  /**
   * {@inheritdoc}
   */
  protected function interact(InputInterface $input, OutputInterface $output)
  {
    $dialog = $this->getDialogHelper();
    $dialog->writeSection($output, 'Welcome to the Drupal controller generator');

    // --module option
    $module = $input->getOption('module');
    if (!$module) {
      // @see Drupal\AppConsole\Command\Helper\ModuleTrait::moduleQuestion
      $module = $this->moduleQuestion($input, $output, $dialog);
    }
    $input->setOption('module', $module);

    // --class-name option
    $class_name = $input->getOption('class-name');
    if (!$class_name) {
      $name = $dialog->ask(
        $output,
        $dialog->getQuestion('Enter the controller name', 'DefaultController'),
        'DefaultController'
      );
    }
    $input->setOption('class-name', $name);

    // --test option
    $test = $input->getOption('test');
    if (!$test && $dialog->askConfirmation(
      $output,
      $dialog->getQuestion('Generate Test Unit?', 'yes', '?'),
      TRUE
    )) {
      $test = true;
    }
    $input->setOption('test', $test);

    // --services option
    // @see use Drupal\AppConsole\Command\Helper\ServicesTrait::servicesQuestion
    $services_collection = $this->servicesQuestion($input, $output, $dialog);
    $input->setOption('services', $services_collection);

    // --routing option
    $routing = $input->getOption('routing');
    if (!$routing && $dialog->askConfirmation(
      $output,
      $dialog->getQuestion('Update routing file?', 'yes', '?'),
      true
    )) {
      $routing = true;
    }
    $input->setOption('routing', $routing);
  }

  /**
   * @return \Drupal\AppConsole\Generator\ControllerGenerator
   */
  protected function createGenerator()
  {
    return new ControllerGenerator();
  }
}
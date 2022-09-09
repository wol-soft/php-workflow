[![Latest Version](https://img.shields.io/packagist/v/wol-soft/php-workflow.svg)](https://packagist.org/packages/wol-soft/php-workflow)
[![Minimum PHP Version](https://img.shields.io/badge/php-%3E%3D%207.4-8892BF.svg)](https://php.net/)
[![Maintainability](https://api.codeclimate.com/v1/badges/a7c6d1c276d2a6aba61e/maintainability)](https://codeclimate.com/github/wol-soft/php-workflow/maintainability)
[![Build Status](https://github.com/wol-soft/php-workflow/actions/workflows/main.yml/badge.svg)](https://github.com/wol-soft/php-workflow/actions/workflows/main.yml)
[![Coverage Status](https://coveralls.io/repos/github/wol-soft/php-workflow/badge.svg)](https://coveralls.io/github/wol-soft/php-workflow)
[![MIT License](https://img.shields.io/packagist/l/wol-soft/php-workflow.svg)](https://github.com/wol-soft/php-workflow/blob/master/LICENSE)

# php-workflow

Create controlled workflows from small pieces.

This library provides a predefined set of stages to glue together your workflows.
You implement small self-contained pieces of code and define the execution order - everything else will be done by the execution control.

Bonus: you will get an execution log for each executed workflow - if you want to see what's happening.

## Table of Contents ##

* [Workflow vs. process](#Workflow-vs-process)
* [Installation](#Installation)
* [Example workflow](#Example-workflow)
* [Workflow container](#Workflow-container)
* [Stages](#Stages)
* [Workflow control](#Workflow-control)
* [Nested workflows](#Nested-workflows)
* [Loops](#Loops)
* [Step dependencies](#Step-dependencies)
  * [Required container values](#Required-container-values)
* [Error handling, logging and debugging](#Error-handling-logging-and-debugging)
  * [Custom output formatter](#Custom-output-formatter)
* [Tests](#Tests)
* [Workshop](#Workshop)

## Workflow vs. process

Before we start to look at coding with the library let's have a look, what a workflow implemented with this library can and what a workflow can't.

Let's assume we want to sell an item via an online shop.
If a customer purchases an item he walks through the process of purchasing an item.
This process contains multiple steps.
Each process step can be represented by a workflow implemented with this library. For example:

* Customer registration
* Add items to the basket
* Checkout the basket
* ...

This library helps you to implement the process steps in a structured way.
It doesn't control the process flow.

Now we know which use cases this library aims at. Now let's install the library and start coding.

## Installation

The recommended way to install php-workflow is through [Composer](http://getcomposer.org):

```
$ composer require wol-soft/php-workflow
```

Requirements of the library:

- Requires at least PHP 7.4

## Example workflow

Let's have a look at a code example first.
Our example will represent the code to add a song to a playlist in a media player.
Casually you will have a controller method which glues together all necessary steps with many if's, returns, try-catch blocks and so on.
Now let's have a look at a possible workflow definition:

```php
$workflowResult = (new \PHPWorkflow\Workflow('AddSongToPlaylist'))
    ->validate(new CurrentUserIsAllowedToEditPlaylistValidator())
    ->validate(new PlaylistAlreadyContainsSongValidator())
    ->before(new AcceptOpenSuggestionForSong())
    ->process(new AddSongToPlaylist())
    ->onSuccess(new NotifySubscribers())
    ->onSuccess(new AddPlaylistLogEntry())
    ->onSuccess(new UpdateLastAddedToPlaylists())
    ->onError(new RecoverLog())
    ->executeWorkflow();
```

This workflow may create an execution log which looks like the following (more examples coming up later):

```
Process log for workflow 'AddSongToPlaylist':
Validation:
  - Check if the playlist is editable: ok
  - Check if the playlist already contains the requested song: ok
Before:
  - Accept open suggestions for songs which shall be added: skipped (No open suggestions for playlist)
Process:
  - Add the songs to the playlist: ok
    - Appended song at the end of the playlist
    - New playlist length: 2
On Success:
  - Notify playlist subscribers about added song: ok
    - Notified 5 users
  - Persist changes in the playlist log: ok
  - Update the users list of last contributed playlists: ok

Summary:
  - Workflow execution: ok
    - Execution time: 45.27205ms
```

Now let's check what exactly happens.
Each step of your workflow is represented by an own class which implements the step.
Steps may be used in multiple workflows (for example the **CurrentUserIsAllowedToEditPlaylistValidator** can be used in every workflow which modifies playlists).
Each of these classes representing a single step must implement the **\PHPWorkflow\Step\WorkflowStep** interface.
Until you call the **executeWorkflow** method no step will be executed.

By calling the **executeWorkflow** method the workflow engine is triggered to start the execution with the first used stage.
In our example the validations will be executed first.
If all validations are successfully the next stage will be executed otherwise the workflow execution will be cancelled.

Let's have a more precise look at the implementation of a single step through the example of the *before* step **AcceptOpenSuggestionForSong**.
Some feature background to understand what's happening in our example: our application allows users to suggest songs for playlists.
If the owner of a playlist adds a song to a playlist which already exists as an open suggestion the suggestion shall be accepted instead of adding the song to the playlist and leave the suggestion untouched.
Now let's face the implementation with some inline comments to describe the workflow control:

```php
class AcceptOpenSuggestionForSong implements \PHPWorkflow\Step\WorkflowStep {
    /**
     * Each step must provide a description. The description will be used in the debug
     * log of the workflow to get a readable representation of an executed workflow 
     */
    public function getDescription(): string
    {
        return 'Accept open suggestions for songs which shall be added to a playlist';
    }

    /**
     * Each step will get access to two objects to interact with the workflow.
     * First the WorkflowControl object $control which provides methods to skip
     * steps, mark tests as failed, add debug information etc.
     * Second the WorkflowContainer object $container which allows us to get access
     * to various workflow related objects.
     */
    public function run(
        \PHPWorkflow\WorkflowControl $control,
        \PHPWorkflow\State\WorkflowContainer $container
    ) {
        $openSuggestions = (new SuggestionRepository())
            ->getOpenSuggestionsByPlaylistId($container->get('playlist')->getId());

        // If we detect a condition which makes a further execution of the step
        // unnecessary we can simply skip the further execution.
        // By providing a meaningful reason our workflow debug log will be helpful.
        if (empty($openSuggestions)) {
            $control->skipStep('No open suggestions for playlist');
        }

        foreach ($openSuggestions as $suggestion) {
            if ($suggestion->getSongId() === $container->get('song')->getId()) {
                if ((new SuggestionService())->acceptSuggestion($suggestion)) {
                    // If we detect a condition where the further workflow execution is
                    // unnecessary we can skip the further execution.
                    // In this example the open suggestion was accepted successfully so
                    // the song must not be added to the playlist via the workflow.
                    $control->skipWorkflow('Accepted open suggestion');
                }

                // We can add warnings to the debug log. Another option in this case could
                // be to call $control->failWorkflow() if we want the workflow to fail in
                // an error case.
                // In our example, if the suggestion can't be accepted, we want to add the
                // song to the playlist via the workflow.
                $control->warning("Failed to accept open suggestion {$suggestion->getId()}");
            }
        }

        // for completing the debug log we mark this step as skipped if no action has been
        // performed. If we don't mark the step as skipped and no action has been performed
        // the step will occur as 'ok' in the debug log - depends on your preferences :)
        $control->skipStep('No matching open suggestion');
    }
}
```

## Workflow container

Now let's have a more detailed look at the **WorkflowContainer** which helps us, to share data and objects between our workflow steps.
The relevant objects for our example workflow is the **User** who wants to add the song, the **Song** object of the song to add and the **Playlist** object.
Before we execute our workflow we can set up a **WorkflowContainer** which contains all relevant objects:

```php
$workflowContainer = (new \PHPWorkflow\State\WorkflowContainer())
    ->set('user', Session::getUser())
    ->set('song', (new SongRepository())->getSongById($request->get('songId')))
    ->set('playlist', (new PlaylistRepository())->getPlaylistById($request->get('playlistId')));
```

The workflow container provides the following interface:

```php
// returns an item or null if the key doesn't exist
public function get(string $key)
// set or update a value
public function set(string $key, $value): self
// remove an entry
public function unset(string $key): self
// check if a key exists
public function has(string $key): bool
```

Each workflow step may define requirements, which entries must be present in the workflow container before the step is executed.
For more details have a look at [Required container values](#Required-container-values).

Alternatively to set and get the values from the **WorkflowContainer** via string keys you can extend the **WorkflowContainer** and add typed properties/functions to handle values in a type-safe manner:

```php
class AddSongToPlaylistWorkflowContainer extends \PHPWorkflow\State\WorkflowContainer {
    public function __construct(
        public User $user,
        public Song $song,
        public Playlist $playlist,
    ) {}
}

$workflowContainer = new AddSongToPlaylistWorkflowContainer(
    Session::getUser(),
    (new SongRepository())->getSongById($request->get('songId')),
    (new PlaylistRepository())->getPlaylistById($request->get('playlistId')),
);
```

When we execute the workflow via **executeWorkflow** we can inject the **WorkflowContainer**.

```php
$workflowResult = (new \PHPWorkflow\Workflow('AddSongToPlaylist'))
    // ...
    ->executeWorkflow($workflowContainer);
```

Another possibility would be to define a step in the **Prepare** stage (e.g. **PopulateAddSongToPlaylistContainer**) which populates the automatically injected empty **WorkflowContainer** object.

## Stages

The following predefined stages are available when defining a workflow:

* Prepare
* Validate
* Before
* Process
* OnSuccess
* OnError
* After

Each stage has a defined set of stages which may be called afterwards (e.g. you may skip the **Before** stage).
When setting up a workflow your IDE will support you by suggesting only possible next steps via autocompletion.
Each workflow must contain at least one step in the **Process** stage.

Any step added to the workflow may throw an exception. Each exception will be caught and is handled like a failed step.
If a step in the stages **Prepare**, **Validate** (see details for the stage) or **Before** fails, the workflow is failed and will not be executed further.

Any step may skip or fail the workflow via the **WorkflowControl**.
If the **Process** stage has been executed and any later step tries to fail or skip the whole workflow it's handled as a failed/skipped step.

Now let's have a look at some stage-specific details.

### Prepare

This stage allows you to add steps which must be executed before any validation or process execution is triggered.
Steps may contain data loading, gaining workflow relevant semaphores, etc.

### Validate

This stage allows you to execute validations.
There are two types of validations: hard validations and soft validations.
All hard validations of the workflow will be executed before the soft validations.
If a hard validation fails the workflow will be stopped immediately (e.g. access right violations).
All soft validations of the workflow will be executed independently of their result.
All failing soft validations will be collected in a **\PHPWorkflow\Exception\WorkflowValidationException** which is thrown at the end of the validation stage if any of the soft validations failed.

When you attach a validation to your workflow the second parameter of the **validate** method defines if the validation is a soft or a hard validation:

```php

$workflowResult = (new \PHPWorkflow\Workflow('AddSongToPlaylist'))
    // hard validator: if the user isn't allowed to edit the playlist
    // the workflow execution will be cancelled immediately
    ->validate(new CurrentUserIsAllowedToEditPlaylistValidator(), true)
    // soft validators: all validators will be executed
    ->validate(new PlaylistAlreadyContainsSongValidator())
    ->validate(new SongGenreMatchesPlaylistGenreValidator())
    ->validate(new PlaylistContainsNoSongsFromInterpret())
    // ...
```

In the provided example any of the soft validators may fail (e.g. the **SongGenreMatchesPlaylistGenreValidator** checks if the genre of the song matches the playlist, the **PlaylistContainsNoSongsFromInterpret** may check for duplicated interprets).
The thrown **WorkflowValidationException** allows us to determine all violations and set up a corresponding error message.
If all validators pass the next stage will be executed.

### Before

This stage allows you to perform preparatory steps with the knowledge that the workflow execution is valid.
This steps may contain the allocation of resources, filtering the data to process etc.

### Process

This stage contains your main logic of the workflow. If any of the steps fails no further steps of the process stage will be executed.

### OnSuccess

This stage allows you to define steps which shall be executed if all steps of the **Process** stage are executed successfully.
For example logging, notifications, sending emails, etc.

All steps of the stage will be executed, even if some steps fail. All failing steps will be reported as warnings.

### OnError

This stage allows you to define steps which shall be executed if any step of the **Process** stage fails.
For example logging, setting up recovery data, etc.

All steps of the stage will be executed, even if some steps fail. All failing steps will be reported as warnings.

### After

This stage allows you to perform cleanup steps after all other stages have been executed. The steps will be executed regardless of the successful execution of the **Process** stage.

All steps of the stage will be executed, even if some steps fail. All failing steps will be reported as warnings.

## Workflow control

The **WorkflowControl** object which is injected into each step provides the following interface to interact with the workflow:

```php
// Mark the current step as skipped.
// Use this if you detect, that the step execution is not necessary
// (e.g. disabled by config, no entity to process, ...)
public function skipStep(string $reason): void;

// Mark the current step as failed. A failed step before and during the processing of
// a workflow leads to a failed workflow.
public function failStep(string $reason): void;

// Mark the workflow as failed. If the workflow is failed after the process stage has
// been executed it's handled like a failed step.
public function failWorkflow(string $reason): void;

// Skip the further workflow execution (e.g. if you detect it's not necessary to process
// the workflow). If the workflow is skipped after the process stage has been executed
// it's handled like a skipped step.
public function skipWorkflow(string $reason): void;

// Useful when using loops to cancel the current iteration (all upcoming steps).
// If used outside a loop, it behaves like skipStep.
public function continue(string $reason): void;

// Useful when using loops to break the loop (all upcoming steps and iterations).
// If used outside a loop, it behaves like skipStep.
public function break(string $reason): void;

// Attach any additional debug info to your current step.
// The infos will be shown in the workflow debug log.
public function attachStepInfo(string $info): void

// Add a warning to the workflow.
// All warnings will be collected and shown in the workflow debug log.
// You can provide an additional exception which caused the warning.
// If you provide the exception, exception details will be added to the debug log.
public function warning(string $message, ?Exception $exception = null): void;
```

## Nested workflows

If some of your steps become more complex you may want to have a look into the `NestedWorkflow` wrapper which allows you to use a second workflow as a step of your workflow:

```php
$parentWorkflowContainer = (new \PHPWorkflow\State\WorkflowContainer())->set('parent-data', 'Hello');
$nestedWorkflowContainer = (new \PHPWorkflow\State\WorkflowContainer())->set('nested-data', 'World');

$workflowResult = (new \PHPWorkflow\Workflow('AddSongToPlaylist'))
    ->validate(new CurrentUserIsAllowedToEditPlaylistValidator())
    ->before(new \PHPWorkflow\Step\NestedWorkflow(
        (new \PHPWorkflow\Workflow('AcceptOpenSuggestions'))
            ->validate(new PlaylistAcceptsSuggestionsValidator())
            ->before(new LoadOpenSuggestions())
            ->process(new AcceptOpenSuggestions())
            ->onSuccess(new NotifySuggestor()),
        $nestedWorkflowContainer,
    ))
    ->process(new AddSongToPlaylist())
    ->onSuccess(new NotifySubscribers())
    ->executeWorkflow($parentWorkflowContainer);
```

Each nested workflow must be executable (contain at least one **Process** step).

The debug log of your nested workflow will be embedded in the debug log of your main workflow.

As you can see in the example you can inject a dedicated **WorkflowContainer** into the nested workflow.
The nested workflow will gain access to a merged **WorkflowContainer** which provides all data and methods of your main workflow container and your nested container.
If you add additional data to the merged container the data will be present in your main workflow container after the nested workflow execution has been completed.
For example your implementations of the steps used in the nested workflow will have access to the keys `nested-data` and `parent-data`.

## Loops

If you handle multiple entities in your workflows at once you may need loops.
An approach would be to set up a single step which contains the loop and all logic which is required to be executed in a loop.
But if there are multiple steps required to be executed in the loop you may want to split the step into various steps.
By using the `Loop` class you can execute multiple steps in a loop.
For example let's assume our `AddSongToPlaylist` becomes a `AddSongsToPlaylist` workflow which can add multiple songs at once:

```php
$workflowResult = (new \PHPWorkflow\Workflow('AddSongToPlaylist'))
    ->validate(new CurrentUserIsAllowedToEditPlaylistValidator())
    ->process(
        (new \PHPWorkflow\Step\Loop(new SongLoop()))
            ->addStep(new AddSongToPlaylist())
            ->addStep(new ClearSongCache())
    )
    ->onSuccess(new NotifySubscribers())
    ->executeWorkflow($workflowContainer);
```

Our process step now implements a loop controlled by the `SongLoop` class.
The loop contains our two steps `AddSongToPlaylist` and `ClearSongCache`.
The implementation of the `SongLoop` class must implement the `PHPWorkflow\Step\LoopControl` interface.
Let's have a look at an example implementation:

```php
class SongLoop implements \PHPWorkflow\Step\LoopControl {
    /**
     * As well as each step also each loop must provide a description.
     */
    public function getDescription(): string
    {
        return 'Loop over all provided songs';
    }

    /**
     * This method will be called before each loop run.
     * $iteration will contain the current iteration (0 on first run etc)
     * You have access to the WorkflowControl and the WorkflowContainer.
     * If the method returns true the next iteration will be executed.
     * Otherwise the loop is completed.
     */
    public function executeNextIteration(
        int $iteration,
        \PHPWorkflow\WorkflowControl $control,
        \PHPWorkflow\State\WorkflowContainer $container
    ): bool {
        // all songs handled - end the loop
        if ($iteration === count($container->get('songs'))) {
            return false;
        }

        // add the current song to the container so the steps
        // of the loop can access the entry
        $container->set('currentSong', $container->get('songs')[$iteration]);

        return true;
    }
}
```

A loop step may contain a nested workflow if you need more complex steps.

To control the flow of the loop from the steps you can use the `continue` and `break` methods on the `WorkflowControl` object.

By default, a loop is stopped if a step fails.
You can set the second parameter of the `Loop` class (`$continueOnError`) to true to continue the execution with the next iteration.
If you enable this option a failed step will not result in a failed workflow.
Instead, a warning will be added to the process log.
Calls to `failWorkflow` and `skipWorkflow` will always cancel the loop (and consequently the workflow) independent of the option.

## Step dependencies

Each step implementation may apply dependencies to the step.
By defining dependencies you can set up validation rules which are checked before your step is executed (for example: which data must be provided in the workflow  container).
If any of the dependencies is not fulfilled, the step will not be executed and is handled as a failed step.

Note: as this feature uses [Attributes](https://www.php.net/manual/de/language.attributes.overview.php), it is only available if you use PHP >= 8.0.

### Required container values

With the `\PHPWorkflow\Step\Dependency\Required` attribute you can define keys which must be present in the provided workflow container.
The keys consequently must be provided in the initial workflow or be populated by a previous step.
Additionally to the key you can also provide the type of the value (eg. `string`).

To define the dependency you simply annotate the provided workflow container parameter:

```php
public function run(
    \PHPWorkflow\WorkflowControl $control,
    // The key customerId must contain a string
    #[\PHPWorkflow\Step\Dependency\Required('customerId', 'string')]
    // The customerAge must contain an integer. But also null is accepted.
    // Each type definition can be prefixed with a ? to accept null.
    #[\PHPWorkflow\Step\Dependency\Required('customerAge', '?int')]
    // Objects can also be type hinted
    #[\PHPWorkflow\Step\Dependency\Required('created', \DateTime::class)]
    \PHPWorkflow\State\WorkflowContainer $container,
) {
    // Implementation which can rely on the defined keys to be present in the container.
}
```

The following types are supported: `string`, `bool`, `int`, `float`, `object`, `array`, `iterable`, `scalar` as well as object type hints by providing the corresponding FQCN.

## Error handling, logging and debugging

The **executeWorkflow** method returns an **WorkflowResult** object which provides the following methods to determine the result of the workflow:

```php
// check if the workflow execution was successful
public function success(): bool;
// check if warnings were emitted during the workflow execution
public function hasWarnings(): bool;
// get a list of warnings, grouped by stage
public function getWarnings(): array;
// get the exception which caused the workflow to fail
public function getException(): ?Exception;
// get the debug execution log of the workflow
public function debug(?OutputFormat $formatter = null);
// access the container which was used for the workflow
public function getContainer(): WorkflowContainer;
// get the last executed step
// (e.g. useful to determine which step caused a workflow to fail)
public function getLastStep(): WorkflowStep;
```

As stated above workflows with failing steps before the **Process** stage will be aborted, otherwise the **Process** stage and all downstream stages will be executed.

By default, the execution of a workflow throws an exception if an error occurs.
The thrown exception will be a **\PHPWorkflow\Exception\WorkflowException** which allows you to access the **WorkflowResult** object via the **getWorkflowResult** method.

The **debug** method provides an execution log including all processed steps with their status, attached data  as well as a list of all warnings and performance data.

Some example outputs for our example workflow may look like the following.

#### Successful execution

```
Process log for workflow 'AddSongToPlaylist':
Validation:
  - Check if the playlist is editable: ok
  - Check if the playlist already contains the requested song: ok
Before:
  - Accept open suggestions for songs which shall be added: skipped (No open suggestions for playlist)
Process:
  - Add the songs to the playlist: ok
    - Appended song at the end of the playlist
    - New playlist length: 2
On Success:
  - Notify playlist subscribers about added song: ok
    - Notified 5 users
  - Persist changes in the playlist log: ok
  - Update the users list of last contributed playlists: ok

Summary:
  - Workflow execution: ok
    - Execution time: 45.27205ms
```

Note the additional data added to the debug log for the **Process** stage and the **NotifySubscribers** step via the **attachStepInfo** method of the **WorkflowControl**.

#### Failed workflow

```
Process log for workflow 'AddSongToPlaylist':
Validation:
  - Check if the playlist is editable: failed (playlist locked)

Summary:
  - Workflow execution: failed
    - Execution time: 6.28195ms
```

In this example the **CurrentUserIsAllowedToEditPlaylistValidator** step threw an exception with the message `playlist locked`.

#### Workflow skipped

```
Process log for workflow 'AddSongToPlaylist':
Validation:
  - Check if the playlist is editable: ok
  - Check if the playlist already contains the requested song: ok
Before:
  - Accept open suggestions for songs which shall be added: ok (Accepted open suggestion)

Summary:
  - Workflow execution: skipped (Accepted open suggestion)
    - Execution time: 89.56986ms
```

In this example the **AcceptOpenSuggestionForSong** step found a matching open suggestion and successfully accepted the suggestion.
Consequently, the further workflow execution is skipped.

### Custom output formatter

The output of the `debug` method can be controlled via an implementation of the `OutputFormat` interface.
By default a string representation of the execution will be returned (just like the example outputs).

Currently the following additional formatters are implemented:

| Formatter       | Description   |
| --------------- | ------------- |
| `StringLog`     | The default formatter. Creates a string representation. <br />Example:<br />`$result->debug();` |
| `WorkflowGraph` | Creates a SVG file containing a graph which represents the workflow execution. The generated image will be stored in the provided target directory. Requires `dot` executable.<br />Example:<br />`$result->debug(new WorkflowGraph('/var/log/workflow/graph'));` |
| `GraphViz`      | Returns a string containing [GraphViz](https://graphviz.org/) code for a graph representing the workflow execution.  <br />Example:<br />`$result->debug(new GraphViz());`|

## Tests

The library is tested via [PHPUnit](https://phpunit.de/).

After installing the dependencies of the library via `composer update` you can execute the tests with `./vendor/bin/phpunit` (Linux) or `vendor\bin\phpunit.bat` (Windows).
The test names are optimized for the usage of the `--testdox` output.

If you want to test workflows you may include the `PHPWorkflow\Tests\WorkflowTestTrait` which adds methods to simplify asserting workflow results.
The following methods are added to your test classes:

```php
// assert the debug output of the workflow. See library tests for example usages
protected function assertDebugLog(string $expected, WorkflowResult $result): void
// provide a step which you expect to fail the workflow.
// example: $this->expectFailAtStep(MyFailingStep::class, $workflowResult);
protected function expectFailAtStep(string $step, WorkflowResult $result): void
// provide a step which you expect to skip the workflow.
// example: $this->expectSkipAtStep(MySkippingStep::class, $workflowResult);
protected function expectSkipAtStep(string $step, WorkflowResult $result): void
```

## Workshop

Maybe you want to try out the library and lack a simple idea to solve with the library.
Therefore, here's a small workshop which covers most of the library's features.
Implement the task given below (which, to be fair, can surely be implemented easier without the library but the library is designed to support large workflows with a lot of business logic) to have an idea how coding with the library works.

Your data input for this task is a simple array with a list of persons in the following format:

```php
[
    'firstname' => string,
    'lastname' => string,
    'age' => int,
]
```

The workflow shall implement the following steps:

1. Check if the list is empty. In this case finish the workflow directly
2. Check if the list contains persons with an age below 18 years. In this case the workflow should fail
3. Make sure each firstname and lastname is populated. If any empty fields are detected, the workflow should fail
4. Before processing the list normalize the firstnames and lastnames (`ucfirst` and `trim`)
   - Make sure the workflow log contains the amount of changed data sets
5. Process the list. The processing itself splits up into the following steps:
   1. Make sure a directory of your choice contains a CSV file for each age from the input data
      - If the file doesn't exist, create a new file
      - The workflow log must contain information about new files
   2. Add all persons from your input data to the corresponding files
      - The workflow log must display the amount of persons added to each file
6. If all persons were persisted successfully create a ZIP backup of all files
7. If an error occurred rollback to the last existing ZIP backup

If you have finished implementing the workflow, pick a step of your choice and implement a unit test for the step.

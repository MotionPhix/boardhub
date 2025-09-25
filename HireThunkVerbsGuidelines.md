Quickstart
Let's start with an example of a Subscription service, where a customer begins a free trial.

#Requirements
To use Verbs, first make sure you have the following installed:

Laravel version 10 or later
PHP version 8.1 or later
#Install Verbs
Install Verbs using composer:

composer require hirethunk/verbs
#Publish and Run Migrations
The last thing you need to do before you use Verbs is run migrations:

php artisan vendor:publish --tag=verbs-migrations
php artisan migrate
#Firing your first Event
To generate an event, use the built-in artisan command:

php artisan verbs:event CustomerBeganTrial
This will generate an event in the app/Events directory of your application, with a handle() method baked-in.

For now, replace that with a $customer_id:

class CustomerBeganTrial extends Event
{
public int $customer_id;
}
You can now fire this event anywhere in your code using:

CustomerBeganTrial::fire(customer_id: 1);
(For this example we'll use a normal integer for our customer_id, but Event Sourcing across your app requires Unique IDs).

#Utilizing States
States in Verbs are simple PHP objects containing data which is mutated over time by events.

Say we want to prevent a customer from signing up for a free trial if they already signed up for one in the past year--we can use our state to help us do that.

Let's create a new state using another built-in artisan command:

php artisan verbs:state CustomerState
This will create a CustomerState class in our app/States directory.

We'll customize it to add a timestamp.

class CustomerState extends State
{
public Carbon|null $trial_started_at = null;
}
Now that we have a state, let's tell our event about it.

Back on our event, add and import a #[StateId] attribute above our $customer_id property to tell Verbs that we want to look up the CustomerState using this particular id.

class CustomerBeganTrial extends Event
{
#[StateId(CustomerState::class)]
public int $customer_id;
}
Now our event can access the data on the state, and vice versa. Let's make it work for our scenario:

We'll add a validate() method, which accepts an instance of CustomerState.
If the validate method returns true, the event can be fired.
If it returns false or throws an exception, the event will not be fired.
We'll add an apply() method, which also accepts an instance of CustomerState, to mutate the state when our event fires.
class CustomerBeganTrial extends Event
{
#[StateId(CustomerState::class)]
public int $customer_id;

    public function validate(CustomerState $state)
    {
        $this->assert(
            $state->trial_started_at === null
            || $state->trial_started_at->diffInDays() > 365,
            'This user has started a trial within the last year.'
        );
    }
 
    public function apply(CustomerState $state)
    {
        $state->trial_started_at = now();
    }
}
(You can read more about apply, validate, and other event hooks, in event lifecycle).

Firing CustomerBeganTrial now will allow the customer to start our free trial. Firing it again will cause it to fail validation and not execute.

Let's break down why:

The first time you fire CustomerBeganTrial, validate() will check CustomerState to see that trial_started_at === null, which allows the event to fire.
Then, it will apply() the now() timestamp to that property on the state.
This means that the next time you fire it (in less than a year), validate() will check the state, and see that $trial_started_at is no longer null, which will break validation.
#Updating the Database
We recommend starting with state-first development to smartly harness the power of events and states, like we did above. Eventually, however, you'll want to create some Eloquent models.

Say you have a Subscription model with database columns customer_id and expires_at; you can add a handle() method to the end of your event to update your table:

// after apply()

public function handle()
{
Subscription::create([
'customer_id' => $this->customer_id,
'expires_at' => now()->addDays(30),
]);
}

Now, when the fired event is committed at the end of the request, a Subscription model will be created.

Events
In Verbs, Events are the source of your data changes. Before we fire an event, we give it all the data we need it to track, and we describe in the event exactly what it should do with that data once it's been fired.

#Generating an Event
To generate an event, use the built-in artisan command:

php artisan verbs:event CustomerBeganTrial
When you create your first event, it will generate in a fresh app/Events directory.

A brand-new event file will look like this:

class MyEvent extends Event
{
public function handle()
{
// what you want to happen
}
}
#Firing Events
To execute an event, simply call MyEvent::fire() from anywhere in your app.

When you fire the event, any of the event hooks you've added within it, like handle(), will execute.

#Named Parameters
When firing events, include named parameters that correspond to its properties, and vice versa.

// Game model
PlayerAddedToGame::fire(
game_id: $this->id,
player_id: $player->id,
);

// PlayerAddedToGame event
#[StateId(GameState::class)]
public string $game_id;

#[StateId(PlayerState::class)]
public string $player_id;
#Committing
When you fire() an event, it gets pushed to an in-memory queue to be saved with all other Verbs events that you fire. Think of this kind-of like staging changes in git. Events are eventually “committed” in a single database insert. You can usually let Verbs handle this for you, but may also manually commit your events by calling Verbs::commit().

Verbs::commit() is automatically called:

at the end of every request (before returning a response)
at the end of every console command
at the end of every queued job
In tests, you'll often need to call Verbs::commit() manually unless your test triggers one of the above.

#Committing during database transactions
If you fire events during a database transaction, you probably want to call Verbs::commit() before the transaction commits so that your Verbs events are included in the transaction. For example:

DB::transaction(function() {
// Some non-Verbs Eloquent calls

    CustomerRegistered::fire(...);
    CustomerBeganTrial::fire(...);
 
    // …some more non-Verbs Eloquent calls
 
    Verbs::commit();
});
#Committing & immediately accessing results
You can also call Event::commit() (instead of fire()), which will both fire AND commit the event (and all events in the queue). Event::commit() also returns whatever your event’s handle() method returns, which is useful when you need to immediately use the result of an event, such as a store method on a controller.

// CustomerBeganTrial event
public function handle()
{
return Subscription::create([
'customer_id' => $this->customer_id,
'expires_at' => now()->addDays(30),
]);
}

// TrialController
{
public function store(TrialRequest $request) {
$subscription = CustomerBeganTrial::commit(customer_id: Auth::id());
return to_route('subscriptions.show', $subscription);
}
}
#handle()
Use the handle() method included in your event to update your database / models / UI data. You can do most of your complex business logic by utilizing your state, which allows you to optimize your eloquent models to handle your front-facing data. Any States that you type-hint as parameters to your handle() method will be automatically injected for you.

class CustomerRenewedSubscription extends Event
{
#[StateId(CustomerState::class)]
public int $customer_id;

    public function handle(CustomerState $customer)
    {
        Subscription::find($customer->active_subscription_id)
            ->update([
                'renewed_at' => now(),
                'expires_at' => now()->addYear(),
            ]);
    }
}
#Firing additional Events
If you want your event to trigger subsequent events, use the fired() hook.

We'll start with an easy example, then a more complex one. In both, we'll be applying event data to your state only. In application, you may still use any of Verbs' event hooks in your subsequent events.

#fired()
CountIncrementedTwice::fire(count_id: $id);

// CountIncrementedTwice event
public function fired()
{
CountIncremented::fire(count_id: $this->count_id);
CountIncremented::fire(count_id: $this->count_id);
}

// CountIncremented event
public function apply(CountState $state)
{
$state->count++;
}

// test or other file
CountState::load($id)->count; // 2
The fired() hook executes in memory after the event fires, but before it's stored in the database. This allows your state to take care of any changes from your first event, and allows you to use the updated state in your next event. In our next example, we'll illustrate this.

Let's say we have a game where a level 4 Player levels up and receives a reward.

PlayerLeveledUp::fire(player_id: $id);

// PlayerLeveledUp event
public function apply(PlayerState $state)
{
$state->level++;
}

public function fired()
{
PlayerRewarded::fire(player_id: $this->player_id);
}

// PlayerRewarded event
public function apply(PlayerState $state)
{
if ($state->level === 5) {
$state->max_inventory = 100;
}
}

// test or other file
PlayerState::load($id)->max_inventory; // 100;
#Naming Events
Describe what (verb) happened to who (noun), in the format of WhoWhat

OrderCancelled, CarLocked, HolyGrailFound

Importantly, events happened, so they should be past tense.

#Replaying Events
Replaying events will rebuild your application from scratch by running through all recorded events in chronological order. Replaying can be used to restore the state after a failure, to update models, or to apply changes in business logic retroactively.

#When to Replay?
After changing your system or architecture, replaying would populate the new system with the correct historical data.

For debugging, auditing, or any such situation where you want to restore your app to a point in time, Replaying events can reconstruct the state of the system at any point in time.

#Executing a Replay
To replay your events, use the built-in artisan command:

php artisan verbs:replay
You may also use Verbs::replay() in files.

Warning
Verbs does not reset any model data that might be created in your event handlers. Be sure to either reset that data before replaying, or confirm that all handle() calls are idempotent. Replaying events without thinking thru the consequences can have VERY negative side effects. Because of this, upon executing the verbs:replay command we will make you confirm your choice, and confirm again if you're in production.
#Preparing for a replay
Backup any important data--anything that's been populated or modified by events.

Truncate all the data that is created by your event handlers. If you don't, you may end up with lots of duplicate data.

#One-time effects
You'll want to tell verbs when effects should NOT trigger on replay (like sending a welcome email). You may use:

#Verbs::unlessReplaying()
Verbs::unlessReplaying(function () {
// one-time effect
});
Or the #[Once] attribute.

#Firing during Replays
During a replay, the system isn't "firing" the event in the original sense (i.e., it's not going through the initial logic that might include checks, validations, or triggering of additional side effects like sending one-time notifications). Instead, it directly applies the changes recorded in the event store.

Event Lifecycle
Verbs Event Lifecycle Diagram

#Firing Events
When you fire a Verbs event for the first time, it passes through three major phases (firing, fired, and committed) — each with its own individual steps.

#“Firing” Phase
Before your event can be applied, we must make sure it has all the data necessary, and check to see if it's valid. The entire Firing phase only happens when an event is first fired (not when events are re-applied to State or replayed).

#__construct()
The event constructor is only called once when the event is first fired. It will not be called again if the event is ever replayed or re-applied to State.

By the time the constructor has finished, the event should have all the data it needs for the rest of the event lifecycle (you shouldn't do any data retrieval after this point).

#Authorize
Use the authorize method on your Event to ensure that the current user is allowed to fire it. The authorize method behaves exactly the same as Laravel form requests.

#Validate
Use the validate hook to ensure that an event can be fired given the current State(s). Any method on your event that starts with validate is considered a validation method, and validation may run for each state the event is firing on (based on what you type-hint).

For example, an event that fires on two States might have two validation methods:

class UserJoinedTeam
{
// ...

    public function validateUser(UserState $user)
    {
        $this->assert($user->can_join_teams, 'This user must upgrade before joining a team.');
    }
 
    public function validateTeam(TeamState $team)
    {
        $this->assert($team->seats_available > 0, 'This team does not have any more seats available.');
    }
}
#“Fired” Phase
Before your event is saved to the database, and any side effects are triggered, it needs to apply to any state. This lets you update your "event world" before the rest of your application is impacted.

#Apply
Use the apply hook to update the state for the given event. Any method on your event that starts with apply is considered an apply method, and may be called for each state the event is firing on (based on what you type-hint). Apply hooks happen immediately after the event is fired, and also any time that state needs to be re-built from existing events (i.e. if your snapshots are deleted for some reason).

For example, an event that fires on two States might have two apply methods:

class UserJoinedTeam
{
// ...

    public function applyToUser(UserState $user)
    {
        $user->team_id = $this->team_id;
    }
 
    public function applyToTeam(TeamState $team)
    {
        $team->team_seats--;
    }
}
#Fired
Use the fired hook for anything that needs to happen after the event has been fired, but before it's handled. This is typically used for firing additional events.

#“Committed” Phase
Once the event has been fired and stored to the database (committed), it's now safe to trigger side effects.

#Handle
Use the handle hook to perform actions based on your event. This is often writing to the database (sometimes called a "projection"). You can read more about the handle hook in the Events docs.

#Replaying Events
When you replay a Verbs event, it does not pass through these same phases. It's best to think of replays as something that happens later to the same event. During replay, only two lifecycle hooks are called:

Apply
Handle
If you do not want your handle method to re-run during replay, you can either use the Once attribute, or use the Verbs::unlessReplaying helper.

Metadata
If you find yourself wanting to include some additional data on every event, Verbs makes it very easy to automatically include metadata.

In a ServiceProvider or Middleware call the following method:

Verbs::createMetadataUsing(function (Metadata $metadata, Event $event) {
$metadata->team_id = current_team_id();
});
You can call this method as many times as you would like. This is particularly useful for third-party packages, allowing them to add metadata automatically.

It's also possible to simply return an array (or Collection), and Verbs will merge that in for you:

Verbs::createMetadataUsing(fn () => ['team_id' => current_team_id()]);
This is particularly useful for events where accompanying data is moreso about the events, and doesn't necessarily need to be a param in the event.

You can use the $event->metadata() method to get the metadata from the event.
#Toggling Metadata
Maybe you don't want every event to have metadata. Verbs makes it easy to opt out when you need to.

Here's an example of a user who prefers no promotional notifications:

public function sendPromotionalNotification($user)
{
$user_preferences = $this->getUserPreferences($user->id);

    Verbs::createMetadataUsing(fn (Metadata $metadata) => [
        'suppress_notifications' => !$userPreferences->acceptsPromotionalNotifications,
    ]);
 
    PromotionalEvent::fire(details: $user->location->promoDetails());
 
    // resets Metadata bool for the next user
    Verbs::createMetadataUsing(fn (Metadata $metadata) => ['suppress_notifications' => false]);
}
Then, where you handle your promotional event messages:

public function handlePromotionalEvent(PromotionalEvent $event)
{
if ($event->metadata('suppress_notifications', false)) {
return;
}

    $this->sendNotification($event->details);
}

Attributes
##[StateId]
Link your event to its state(s) with the StateId attribute

class YourEvent extends Event
{
#[StateId(GameState::class)]
public int $game_id;

    #[StateId(PlayerState::class)]
    public int $player_id;
}
The StateId attribute takes a state_type, an optional alias string, and by default can automatically generate(autofill) a snowflake_id for you.

##[AppliesToState]
Another way to link states and events; like StateId, but using the attributes above the class instead of on each individual id.

#[AppliesToState(GameState::class)]
#[AppliesToState(PlayerState::class)]
class RolledDice extends Event
{
use PlayerAction;

    public function __construct(
        public int $game_id,
        public int $player_id,
        public array $dice,
    )
}
AppliesToState has the same params as StateId, with an additional optional id param (after state_type) if you want to specify which prop belongs to which state.

#[AppliesToState(state_type: GameState::class, id: foo_id)]
#[AppliesToState(state_type: PlayerState::class, id: bar_id)]
class RolledDice extends Event
{
use PlayerAction;

    public function __construct(
        public int $foo_id,
        public int $bar_id,
        public array $dice,
    )
}
Otherwise, with AppliesToState, Verbs will find the id for you based on your State's prefix (i.e. ExampleState would be example, meaning example_id or example_ids would be associated automatically).

In addition to your state_type param, you may also set an optional alias string.

##[AppliesToChildState]
Use the AppliesToChildState attribute on an event class to allow Verbs to access a nested state.

For our example, let's make sure our ParentState has a child_id property pointing to a ChildState by firing a ChildAddedToParent event:

ChildAddedToParent::fire(parent_id: 1, child_id: 2);

// ChildAddedToParent.php
#[AppliesToState(state_type: ParentState::class, id: 'parent_id')]
#[AppliesToState(state_type: ChildState::class, id: 'child_id')]
class ChildAddedToParent extends Event
{
public int $parent_id;

    public int $child_id;
 
    public function applyToParentState(ParentState $state)
    {
        $state->child_id = $this->child_id;
    }
}
class ParentState extends State
{
public int $child_id;
}
class ChildState extends State
{
public int $count = 0;
}
Now that ParentState has a record of our ChildState, we can load the child through the parent with AppliesToChildState.

Let's show this by firing a NestedStateAccessed event with our new attribute:

NestedStateAccessed::fire(parent_id: 1);

// NestedStateAccessed.php
#[AppliesToChildState(
state_type: ChildState::class,
parent_type: ParentState::class,
id: 'child_id'
)]
class NestedStateAccessed extends Event
{
#[StateId(ParentState::class)]
public int $parent_id;

    public function apply(ChildState $state)
    {
        $state->count++; // 1
    }
}
AppliesToChildState takes a state_type (your child state), parent_type, id (your child state id), and an optional alias string.

When you use AppliesToChildState, don't forget to also use StateId or AppliesToState to identify the parent_id.

##[Once]
Use above any handle() method that you do not want replayed.

class YourEvent extends Event
{
#[Once(YourState::class)]
public function handle()
{
//
}
}

(You may also use Verbs::unlessReplaying, mentioned in one-time effects)

IDs
By default, Verbs uses 64-bit integer IDs called "Snowflakes."

#Globally Unique Ids
We do this because an event-sourcing system needs globablly-unique IDs to run well. Replaying events is a powerful feature, but does not pair well with standard auto-incrementing IDs. Unique IDs help us both minimize collisions, so that each event is executed with fidelity, and maximize interoperability.

We recommend Snowflakes because they are sortable, time-based, and are integers. You may also use ULIDs or UUIDs instead; this can be configured in config/verbs.php. However, they each introduce some complexity. Both are strings, and UUIDs are not sortable.

#Snowflakes in Verbs
Verbs uses glhd/bits under the hood, and you can use it too. Bits makes it easy to use Snowflakes in Laravel. If you're planning to run an app on more than one app server, check out Bits configuration.

A helper method you can use to generate a snowflake right out of the box: snowflake_id()

For models that you're going to manage via events, pull in the HasSnowflakes trait:

use Glhd\Bits\Database\HasSnowflakes;
use Glhd\Bits\Snowflake;

class JobApplication extends Model
{
use HasSnowflakes; // Add this to your model

    // Any attribute can be cast to a `Snowflake` (or `Sonyflake`)
    protected function casts(): array
    {
        return [
            'id' => Snowflake::class,
        ];
    }
}
Bits also provides helpers for your migrations:

/**
* Run the migrations.
  */
  public function up(): void
  {
  Schema::create('job_applications', function (Blueprint $table) {
  $table->snowflakeId();
  $table->snowflake('user_id')->index();
  $table->foreign('user_id')->references('id')->on('users');
  // ...
  });
  }
  The snowflakeId() method creates a new primary key column with a default name of 'id'. The snowflake() method adds a regular snowflake column which is ideal for creating foreign keys.

#Automatically generate snowflake ids
Verbs allows for snowflake_id auto-generation by default when using most of our attributes. By setting your event's state_id property to null--

class CustomerBeganTrial extends Event
{
#[StateId(CustomerState::class)]
public ?int $customer_id = null;
}
--and setting no id value when you fire your event, you allow Verbs' autofill default to provide a snowflake_id() for you.

$event = CustomerBeganTrial::fire() // no set customer_id

$event->customer_id; // = snowflake_id()
If you wish to disable autofill for some reason, you may set it to false in your attributes.

State-first Development
It's incredibly helpful to understand that events influence states first, and models last.

#Events -> States -> Models
The important distinction is that, when you're using event sourcing, state is part of your event system, and models are mostly for your application UI. It should always be possible to delete all the models that are created and updated via events, and rebuild them all by replaying your events.

Our event lifecycle was built to emphasize this: before we even fire an event, we can check you are authorized to do so, we can then check its validation against the state, and the first place where your event data is applied is to the state. The LAST method in the event lifecycle is the handle() method, which is where you modify your model.

Though it's not required, we find it's good practice to order our event functions in the order they're executed in the event lifecycle.

#Leveraging the State
Events affect states in memory, so they are available before they are persisted to the DB.

States allow you to complete your complex calculations and business logic away from you models, radically reducing database query overhead.

Here's a simple example of a nondescript game where players exchange money. The PlayerTransaction event fires:

public function apply(PlayerState $state)
{
$state->wealth += $this->amount;
}

public function handle()
{
// our apply method has already happened!

    Player::fromId($this->player_id)
        ->update([
            'wealth' => $this->state(PlayerState::class)->wealth,
        ]);
}
Because we've told our state to care about this property, we can keep track of all the changes there, grab the calculation output right from the state, and send it to our database.

#Don't mix Models and States
In general, mixing Eloquent models with your event data can have unintended consequences, especially when it comes to replaying events. For example, imagine that you fire an event that creates a model, and then store that model's ID in a subsequent event. If you ever replay your events, the resulting model may have a different auto-incremented ID, and so your later event will unintentionally reference the wrong model.

You can mitigate this issue by always using Snowflakes or ULIDs across your entire app, but it's still generally a bad idea. Because of this, Verbs will trigger an exception if you ever try to store a reference to a model inside your events or states.

If you really know what you're doing, you can disable this behavior with:

Thunk\Verbs\Support\Normalization\ModelNormalizer::dangerouslyAllowModelNormalization();
As the method name suggests, this is not recommended and may have unintended consequences.

States
States in Verbs are simple PHP objects containing data which is mutated over time by events. If that doesn't immediately give you a strong sense of what a state is, or why you would want one, you're not alone.

#A Mental Model
Over time, you'll find your own analogue to improve your mental model of what a state does. This helps you understand when you need a state, and which events it needs to care about.

Here are some to start:

#Stairs
Events are like steps on a flight of stairs. The entire grouping of stairs is the state, which accumulates and holds every step; the database/models will reflect where we are now that we've traversed the stairs.

#Books
Events are like pages in a book, which add to the story; the state is like the spine--it holds the book together and contains the whole story up to now; the database/models are where we are in the story now that those pages have happened.

#Generating a State
To generate a state, use the built-in artisan command:

php artisan verbs:state ExampleState
When you create your first state, it will generate in a fresh app/States directory.

A brand new state file will look like this:

namespace App\States;

use Thunk\Verbs\State;

class ExampleState extends State
{
// It ain't my birthday but I got my name on the cake - Lil Wayne
}
#States and Events
Like our examples suggest, we use states for tracking changes across our events.

Thunk state files tend to be lean, focusing only on tracking properties and offloading most logic to the events themselves.

#Applying Event data to your State
Use the apply() event hook with your state to update any data you'd like the state to track:

// CountIncremented.php
class CountIncremented extends Event
{
#[StateId(CountState::class)]
public int $example_id;

    public function apply(CountState $state)
    {
        $state->event_count++;
    }
}

// CountState.php
class CountState extends State
{
public $event_count = 0;
}

// test or other file
$id = snowflake_id();

CountIncremented::fire(example_id: $id);
Verbs::commit();
CountState::load($id)->event_count // = 1
If you have multiple states that need to be updated in one event, you can load both in the apply() hook, or even write separate, descriptive apply methods:

public function applyToGameState(GameState $state) {}

public function applyToPlayerState(PlayerState $state) {}
On fire(), Verbs will find and call all relevant state and event methods prefixed with "apply".

#Validating Event data using your State
It's possible to use your state to determine whether or not you want to fire your event in the first place. We've added a validate() hook for these instances. You can use validate() to check against properties in the state; if it returns false, the event will not fire.

You can use the built-in assert() method in your validate() check

public function validate()
{
$this->assert(
$game->started, // if this has not happened
'Game must be started before a player can join.' // then display this error message
)
}
You can now see how we use the state to hold a record of event data--how we can apply() event data to a particular state, and how we can validate() whether the event should be fired by referencing that same state data. These and other hooks that helps us maximize our events and states are located in event lifecycle.

#Loading a State
To retrieve the State, simply call load:

CardState::load($card_id);
The state is loaded once and then kept in memory. Even as you apply() events, it's the same, in-memory copy that's being updated, which allows for real-time updates to the state without additional database overhead.

You can also use loadOrFail() to trigger a StateNotFoundException that will result in a 404 HTTP response if not caught.

#Using States in Routes
States implement Laravel’s UrlRoutable interface, which means you can route to them in the exact same way you would do route-model binding:

Route::get('/users/{user_state}', function(UserState $user_state) {
// $user_state is automatically loaded for you!
});
#Singleton States
You may want a state that only needs one iteration across the entire application—this is called a singleton state. Singleton states require no ID because there is only ever one copy in existence across your entire app.

To tell Verbs to treat a State as a singleton, extend the SingletonState class, rather than State.

class CountState extends SingletonState {}
#Loading the singleton state
Since singletons require no IDs, simply call the singleton() method. Trying to load a singleton state in any other way will result in a BadMethodCall exception.

YourState::singleton();
#State Collections
Your events may sometimes need to affect multiple states.

Verbs supports State Collections out of the box, with several convenience methods:

$event_with_single_state->state(); // State
$event_with_multiple_states->states(); // StateCollection
#alias(?string $alias, State $state)
Allows you to set a shorthand name for any of your states.

$collection->alias('foo', $state_1);
You can also set state aliases by setting them in the optional params of some of our attributes: any #[AppliesTo] attribute, and #[StateId].

#get($key, $default = null)
Like the get() collection method, but also preserves any aliases. Returns a state.

$collection->get(0); // returns the first state in the collection
$collection->get('foo'); // returns the state with the alias
#ofType(string $state_type)
Returns a state collection with only the state items of the given type.

$collection->ofType(FooState::class);
#firstOfType()
Returns the first() state item with the given type.

$collection->firstOfType(FooState::class);
#withId(Id $id)
(Id is a stand-in for Bits|UuidInterface|AbstractUid|int|string)

Returns the collection with only the state items with the given id.

$collection->withId(1);
#filter(?callable $callback = null)
Like the filter() collection method, but also preserves any aliases. Returns a state collection.

$activeStates = $stateCollection->filter(function ($state) {
return $state->isActive;
});
#What should be a State?
We find it a helpful rule of thumb to pair your states to your models. States are there to manage event data in memory, which frees up your models to better serve your frontfacing UI needs. Once you've converted to Unique IDs, you can use your state instance's id to correspond directly to a model instance.

class FooCreated
{
#[StateId(FooState::class)]
public int $foo_id;

    // etc
 
    public function handle()
    {
        Foo::create(
            snowflake: $this->foo_id
        );
    }
}

That said: if you ever find yourself storing complex, nested, multi-faceted data in arrays, collections, or objects on your state, you probably need another state. Particularly if the data in those collections, arrays, or objects is ever going to change.


Testing
We enjoy improving Verbs by providing easy, readable testing affordances.

#Verbs::commit()
When testing verbs events, you'll need to call commit manually.

You may continue manually adding Verbs::commit() after each Event::fire() method; however, we've created Verbs::commitImmediately to issue a blanket commit on all events you fire in tests.

beforeEach(function () {
Verbs::commitImmediately();
});
You may also implement the CommitsImmediately interface directly on an Event. (more about this in VerbsStatesInitialized)

#Assertions
The following Test assert() methods are available to thoroughly check your committing granularly.

Before using these methods, add Verbs::fake() to your test so Verbs can set up a fake event store to isolate the testing environment.

Verbs::assertNothingCommitted();
Verbs::assertCommitted(...);
Verbs::assertNotCommitted(...);
#State Factories
In tests, you may find yourself needing to fire and commit several events in order to bring your State to the point where it actually needs testing.

The State::factory() method allows you to bypass manually building up the State, functioning similarly to Model::factory().

This allows you to call:

BankAccountState::factory()->create(
data: ['balance' => 1337]
id: $bank_account_id
);

// Or, using `id()` syntax:

BankAccountState::factory()
->id($bank_account_id)
->create(
data: ['balance' => 1337]
);
If you accidentally pass an ID into both id() and create(), create() takes precedence.
Or, in the case of a singleton state:

ChurnState::factory()->create(['churn' => 40]);
Next, we'll get into how these factories work, and continue after with some Verbs factory methods you may already be familiar with from Eloquent factories.

#VerbsStateInitialized
Under the hood, these methods will fire (and immediately commit) a new VerbsStateInitialized event, which will fire onto the given state, identified by the id argument (if id is null, we assume it is a singleton) and return a copy of that state.

This is primarily designed for booting up states for testing. If you are migrating non-event-sourced codebases to Verbs, when there is a need to initiate a state for legacy data, it's better to create a custom MigratedFromLegacy event.

You may also change the initial event fired from the StateFactory from VerbsStateInitialized to an event class of your choosing by setting an $intial_event property on your State Factory.

class ExampleStateFactory extends StateFactory
{
protected string $initial_event = ExampleCreated::class;
}
VerbsStateInitialized implements the CommitsImmediately interface detailed above, so if you change from this initial event makes sure to extend the interface on your replacement event.

#Factory Methods
Some methods accept Verbs IDs, which, written longform, could be any of these types: Bits|UuidInterface|AbstractUid|int|string.

For brevity, this will be abbreviated in the following applicable methods as Id.

#count(int $count)
Number of states to create. Returns a StateCollection.

UserState::factory()->count(3)->create();
#id(Id $id)
Set the state ID explicitly (cannot be used with count).

UserState::factory()->id(123)->create();
#state(callable|array $data)
Default data (will be overridden by create).

UserState::factory()->state([ /* state data */ ])->create();
The state function is mostly useful for custom factories.

#create(array $data, Id|null $id = null)
Explicit state data. Returns a State or StateCollection.

UserState::factory()->create([ /* state data */ ]);
#Custom Factories
Verbs makes it possible to create your own custom factories for your states.

Create an ExampleStateFactory class in a new App/States/Factories folder.

namespace App\States\Factories;

use Thunk\Verbs\StateFactory;

class ExampleStateFactory extends StateFactory
{
public function confirmed(): static
{
return $this->state(['confirmed' => true]);
}
}
Now in your ExampleState, link our new custom factory:

public bool $confirmed = false;

public int $example_count = 0;

public static function newFactory(): ExampleStateFactory
{
return ExampleStateFactory::new(static::class);
}
This lets you do:

ExampleState::factory()->confirmed()->create(); // ->confirmed will be true
If you'd like to chain behavior after your Factory create() executes, do so in your configure() method:

#configure()
The configure method in your custom factory allows you to set afterMaking and afterCreating effects ( see laravel docs).

#afterMaking() & afterCreating()
public function configure(): void
{
$this->afterCreating(function (ExampleState $state) {
ExampleEvent::fire(
id: $state->id,
);
});
}
#definition()
Returns an array of default property values for your custom state factory whenever you create().

public function definition(): array
{
return [
'example_count' => 4,
];
}

What is Event Sourcing?
#Event Sourcing Defined
Instead of knowing just the current state of your app, every change (event) that leads to the current state is stored. This allows for a more granular understanding of how the system arrived at its current state and offers the flexibility to reconstruct or analyze the state at any point in time.

Here are some of the advantages of event-sourcing:

Less Database querying:
By having states to track event data over time, we can reduce querying overall, and offload complex querying to states instead of models.
A complete history of changes:
Every event, with all its data, is stored in your events tables--enhancing debugging, decision-making, and analytics.
The ability for your events to be replayed:
A powerful feature, this allows you to update and change your app's architecture while keeping the data you need.

Combating Jargon
In traditional event sourcing, there's a lot of jargon that can make it hard to even get started. In Verbs, we tried to abandon a lot of the jargon for (what we believe are) simpler and more obvious terms.

If you have event sourcing experience, or have heard event sourcing terms before, it may be useful to compare them to what we have in Verbs.

#Aggregates
Aggregates (or Aggregate Roots) are called State in Verbs. Aggregate Root is technically a great term, because they are used to aggregate your events into a single state in the same way that aggregate functions like SUM() or MAX() in a SQL database aggregate a bunch of rows of data into a single value.

Aggregates or States can also be thought of as reducers (like useReducer in React), in that they take a stream of events and reduce them to a single state at a moment in time.

#Projectors
In many event sourcing system, you'll have dedicated Projectors that listen for events and project data in a convenient shape for your views. These are sometimes called Projections or maybe View Models.

In Verbs, while it's possible to register dedicated Projectors, most projection is done in the handle method of an event. For example, an AccountWasDeactivated event may project a cancelled_at timestamp to the Account model.

#Reactors
Reactors are similar to projectors, but they're meant for one-time side effects like sending mail or making external API requests (things that you wouldn't want to happen again if you ever replay your events). In Verbs, there is no formal concept of Reactors. Instead, you can just wrap code that you only want to run once inside of a Verbs::unlessReplaying() check.

#Write Models and Read Models + CQRS
CQRS stands for "Command Query Responsibility Segregation" and is a pattern where writes (commands) and reads (queries) are kept separate. Improved scalability and performance are often cited as reasons to introduce CQRS, but the real benefit for even small applications is the flexibility that it allows. Developers often have to make concessions in their data models to account for both read and write concerns. With event sourcing and separate read and write models, you can build Eloquent (read) models that are 100% custom-tailored to your application UI and access patterns, and create new data through events (writes) that map exactly to what happened in your application.

## EXAMPLES

If you need a deeper understanding, let me show you a game they developed using verbs from the examples on their page in
the following order: 1. https://verbs.thunk.dev/examples/monopoly/intro/readme 2.
https://verbs.thunk.dev/examples/monopoly/state/game 3. https://verbs.thunk.dev/examples/monopoly/state/player 4.
https://verbs.thunk.dev/examples/monopoly/setup-events/game-started 5.
https://verbs.thunk.dev/examples/monopoly/setup-events/player-joined-game 6.
https://verbs.thunk.dev/examples/monopoly/setup-events/first-player-selected 7.
https://verbs.thunk.dev/examples/monopoly/gameplay-events/rolled-dice 8.
https://verbs.thunk.dev/examples/monopoly/gameplay-events/player-moved 9.
https://verbs.thunk.dev/examples/monopoly/gameplay-events/purchased-property 10.
https://verbs.thunk.dev/examples/monopoly/gameplay-events/paid-rent 11.
https://verbs.thunk.dev/examples/monopoly/gameplay-events/ended-turn Or if you still want to see how the subscriptions
example works, here is the order: 1. https://verbs.thunk.dev/examples/subscriptions/events/subscription-started 2.
https://verbs.thunk.dev/examples/subscriptions/events/subscription-cancelled 3.
https://verbs.thunk.dev/examples/subscriptions/events/global-report-generated 4.
https://verbs.thunk.dev/examples/subscriptions/events/plan-report-generated 5.
https://verbs.thunk.dev/examples/subscriptions/events/reset-global-report-state 6.
https://verbs.thunk.dev/examples/subscriptions/events/reset-plan-report-state 7.
https://verbs.thunk.dev/examples/subscriptions/state/subscription 8.
https://verbs.thunk.dev/examples/subscriptions/state/plan-report 9.
https://verbs.thunk.dev/examples/subscriptions/state/global-report

# Introduction
Inertia Modal is part of Inertia UI, a suite of packages designed for Laravel, Inertia.js, and Tailwind CSS. With Inertia Modal, you can easily open any route in a Modal or Slideover without having to change anything about your existing routes or controllers.

## Here's a summary of the features:

- Zero backend configuration
- Super simple frontend API
- Support for Base Route / URL
- Modal and slideover support
- Headless support
- Nested/stacked modals support
- Reusable modals
- Multiple sizes and positions
- Reload props in modals
- Easy communication between nested/stacked modals
- Highly configurable

# Example

The package comes with two components: Modal and ModalLink. ModalLink is very similar to Inertia's built-in Link component, but it opens the linked route in a modal instead of a full page load. So, if you have a link that you want to open in a modal, you can simply replace Link with ModalLink.

```vue
<script setup>
import { ModalLink } from '@inertiaui/modal-vue'
</script>

<template>
    <ModalLink href="/users/create">Create User</ModalLink>
</template>
```

The page you linked can then use the Modal component to wrap its content in a modal.

```vue
<script setup>
import { Modal } from '@inertiaui/modal-vue'
</script>

<template>
    <Modal>
        <h1>Create User</h1>
        <form>
            <!-- Form fields -->
        </form>
    </Modal>
</template>
```

That's it! There is no need to change anything about your routes or controllers!

## Installation

There are two ways to install Inertia Modal. First, you can install the package with Composer. This package contains the Laravel package as well as the frontend packages. The advantage of installing it with Composer is that the frontend package is always in sync with the backend package. Alternatively, you can use npm to install the frontend package separately, but this requires you to manage the versions of the frontend and backend packages yourself.

## Composer Installation

```terminal
composer require inertiaui/modal
```

After installing the package, you can link the React or Vue package into your project. This will create a symlink in your node_modules directory to the package in the vendor directory.

```terminal
npm install vendor/inertiaui/modal/vue
```

## NPM Installation

You may also install the frontend packages separately.

```terminal
npm install @inertiaui/modal-vue
```

## Inertia.js Configuration
Inertia Modal requires a root-component to be mounted in your app. You can do this in the main app.ts file where you initialize your Inertia app using the createInertiaApp function. You only need to change the render function to include the renderApp method and pass the App component and props object to it.

```javascript
import { renderApp } from '@inertiaui/modal-vue'

createInertiaApp({
    setup({ el, App, props, plugin }) {
        return
          - createApp({ render: () => h(App, props) }) // Remove this line
          +  createApp({ render: renderApp(App, props) }) // Add this line instead
            .use(plugin)
            .mount(el)
    }
})
```

## Tailwind Configuration

Inertia Modal uses Tailwind CSS for styling. You need to include the package path in the content array of your tailwind.config.js file:

```javascript
export default {
    content: [
        './node_modules/@inertiaui/modal-vue/src/**/*.{js,vue}',
        // other paths...
    ]
}
```

For Tailwind CSS v4, add the following in your app.css file:
```css
@source '../../node_modules/@inertiaui/modal-vue/src/**/*.{js,vue}';
```

# Basic Usage

As described in the introduction, Inertia Modal is fairly simple to use. There are two main components: Modal and ModalLink. In the sections below, we will cover how to use these components.

To use the components, you need to import them into your page:

```vue
<script setup>
import { Modal, ModalLink } from '@inertiaui/modal-vue'
</script>
```

# Modal Component

The Modal component is used to wrap the content of the modal. You can place any content inside the Modal component, such as forms, tables, or other components.

```vue
<template>
    <Modal>
        <h1>Create User</h1>
        <form>
            <!-- Form fields -->
        </form>
    </Modal>
</template>
```

# Modal Events

The Modal component emits several events that you can listen to:

- **after-leave**: Triggered after the modal has been removed from the DOM.
blur: Triggered when the modal loses focus because another modal is opened on top of it.
- **close**: Triggered when the modal is closed.
- **focus**: Triggered when the modal gains focus because a modal on top of it has been closed.
- **success**: Triggered when the modal has been successfully fetched and opened.

```vue
<template>
    <Modal @close="doSomething">
        <!-- ... -->
    </Modal>
</template>
```

# Customizing the Modal
You may add additional props to the Modal component to customize its behavior and style. Check out the Configuration section for a list of all available props.

# ModalLink Component
The ModalLink component is very similar to Inertia's built-in Link component. You can pass an href prop and additional headers using the headers prop. The component is rendered as a regular anchor tag (<a>), but you can change the tag using the as prop.

```vue
<template>
    <ModalLink href="/users/create">
        Create User
    </ModalLink>
</template>
```

# Method and Data
The method prop allows you to specify the HTTP method that should be used when requesting the modal. By default, the method is set to get. With the data prop, you can pass additional data to the backend.

```vue
<template>
    <ModalLink
        href="/users/create"
        method="post"
        :data="{ default_name: 'John Doe' }"
    >
        Create User
    </ModalLink>
</template>
```

# Custom Tag
The as prop allows you to change the tag that is rendered. You can use it to render a button instead of an anchor tag.

```vue
<template>
    <ModalLink href="/users/create" as="button">
        Create User
    </ModalLink>
</template>
```

# ModalLink Events
In addition to the loading prop, you can also listen to the events emitted by the ModalLink component. You can use the @start and @success events to show a loading spinner or text.

```vue
<script setup>
import { ref } from 'vue'

const loading = ref(false)
</script>

<template>
    <ModalLink @start="loading = true" @success="loading = false">
        <!-- ... -->
    </ModalLink>
</template>
```

In addition to the @start and @success events, there is also a @error event. This event is triggered when the Inertia request fails.

```vue
<template>
    <ModalLink @error="errorToast('Whoops! Something went wrong.')">
        <!-- ... -->
    </ModalLink>
</template>
```

Then there are two more events: @close and @after-leave. The @close event is triggered when the modal is closed, and the @after-leave event is triggered after the modal has been removed from the DOM.

# ModalLink Events and Browser Navigation

The close and after-leave events are not triggered when the modal is closed after navigating to it using the browser's back or forward buttons. This is because Inertia.js rerenders the page, and therefore, the ModalLink components. Closing the modal will not be linked to the original ModalLink component.

# Customizing
Just like the Modal component, you can pass additional props to the ModalLink component to customize its behavior and style. Check out the Configuration section for a list of all available props.

# Programmatic Usage
Instead of using the ModalLink component, you can also open a modal programmatically using the visitModal method.

```vue
<script setup>
import { visitModal } from '@inertiaui/modal-vue'

function createUserModal() {
    visitModal('/users/create')
}
</script>

<template>
    <button @click="createUserModal">Create User</button>
</template>
```

If you want to open a Local Modal, you must prepend the URL with a #:

```vue
visitModal('#confirm-action')
```

The visitModal method accepts a second argument, which is an object with options:

```vue
visitModal('/users/create', {
    method: 'post',
    navigate: true,
    data: { default_name: 'John Doe' },
    headers: { 'X-Header': 'Value' },
    config: {
        slideover: true,
    }
    listeners: {},
    onStart: () => console.log('Start visiting modal'),
    onSuccess: () => console.log('Modal visit successful'),
    onError: () => console.log('Modal visit failed'),
    onClose: () => console.log('Modal closed'),
    onAfterLeave: () => console.log('Modal removed from DOM'),
    queryStringArrayFormat: 'brackets',
})
```

The config option allows you to customize the behavior and style of the modal. You should check out the Configuration section for a list of all available options. The queryStringArrayFormat can be set to either brackets or indices.

# Configuration
The ModalLink and Modal components have a number of props that allow you to customize their behavior and style. Let's take a look at all the available props.

# Available Props
The following props can be used on both the ModalLink and Modal components. The props passed to ModalLink will take precedence over those passed to Modal.

# close-button
The close-button prop allows you to show or hide the close button in the modal. By default, the close button is shown. To render a custom close button, you can check out the Close Modal section.

```vue
<template>
    <ModalLink href="/users/create" :close-button="false">
        Create User
    </ModalLink>
</template>
```

# close-explicitly
The close-explicitly prop allows you to close the modal explicitly. By default, the modal closes when the user clicks outside of the modal or presses the Esc key. If you set close-explicitly to true, the modal will only close when you press the close button or close it programmatically.

```vue
<template>
    <ModalLink href="/users/create" :close-explicitly="true">
        Create User
    </ModalLink>
</template>
```

# max-width
The max-width lets you specify the maximum width of the modal. For modals, the default value is 2xl, and for slideover, the default value is md. These values correspond to Tailwind CSS conventions. Valid values are sm, md, lg, xl, 2xl, 3xl, 4xl, 5xl, 6xl, and 7xl.

```vue
<template>
    <ModalLink href="/users/create" max-width="lg">
        Create User
    </ModalLink>
</template>
```

# padding-classes
The padding-classes prop allows you to add custom padding classes to the modal. This is useful if you want to add extra padding to the modal content or if you want to remove the default padding. The default classes are p-4 sm:p-6.

```vue
<template>
    <ModalLink href="/users/create" padding-classes="p-8">
        Create User
    </ModalLink>
</template>
```

# panel-classes
The panel-classes prop allows you to add custom classes to the panel of the modal. This is useful if you want to add extra styles to the modal panel, such as a border or shadow. The default classes are bg-white rounded for modals and bg-white min-h-screen for slideover.

These classes are merged with the padding classes. They are separated by a different prop to allow for more flexibility.

```vue
<template>
    <ModalLink href="/users/create" panel-classes="bg-blue-50 rounded-lg">
        Create User
    </ModalLink>
</template>
```

# position
The position prop allows you to specify the position of the modal. The default value is center for modals and right for slideover. Valid values for modals are bottom, center, and top. Valid values for slideover are left and right.

```vue
<template>
    <ModalLink href="/users/create" position="top">
        Create User
    </ModalLink>
</template>
```

# slideover
The slideover prop allows you to open the modal as a slideover instead of a modal. The default value is false. When you add the slideover prop, the modal will open as a slideover.

```vue
<template>
    <ModalLink href="/users/create" slideover>
        Create User
    </ModalLink>
</template>
```

# Default configuration
You can set the default configuration for all modals and slideovers by importing the putConfig function from the package, for example, in your app.ts/app.ts file.

```javascript
import { putConfig } from '@inertiaui/modal-vue'
```

You can call the ***putConfig*** function with an object containing the configuration that you want to set as the default. Here is an example with the default configuration. Note that there are separate keys for modals and slideovers.

```javascript
putConfig({
    type: 'modal',
    navigate: false,
    modal: {
        closeButton: true,
        closeExplicitly: false,
        maxWidth: '2xl',
        paddingClasses: 'p-4 sm:p-6',
        panelClasses: 'bg-white rounded',
        position: 'center',
    },
    slideover: {
        closeButton: true,
        closeExplicitly: false,
        maxWidth: 'md',
        paddingClasses: 'p-4 sm:p-6',
        panelClasses: 'bg-white min-h-screen',
        position: 'right',
    },
})
```

Instead of passing a whole object, you can also pass just the keys that you want to override. The other values will be taken from the default configuration.

```javascript
putConfig({
    modal: {
        closeButton: false,
    },
})
```

Alternatively, you can pass a key and a value to the putConfig function using dot notation.

```javascript
putConfig('modal.closeButton', false)
```

# Modal Props
Just like regular Inertia pages, modals can also receive props. This works similarly to how you would pass props to a regular Inertia page. Let's say you have a modal for editing a user:

```vue
return Inertia::render('EditUser', [
    'roles' => Role::pluck('name', 'id'),
    'user' => $user,
]);
```

Then, in your modal component, you can access these props:

```vue
<script setup>
defineProps({ roles: Object, user: Object })
</script>

<template>
    <div>
        <h1>Edit User {{ user.name }}</h1>
        <form>
            <!-- ... -->
        </form>
    </div>
</template>
```

# Accessing Props using useModal
If you're using child components within your modal, you can pass props to them, but you can also access the modal's props using the useModal hook. This is useful when you need to access the modal's props in a child component that is not directly receiving them as props.

```vue
<script setup>
import { useModal } from '@inertiaui/modal-vue'

const { props } = useModal()
</script>

<template>
    <p>User Status: {{ props.user.status }}</p>
</template>
```

# Close Modal
By default, both the ***modal*** and ***slideover*** components have a close button. You can hide this button by using the close-button prop, as documented in the Configuration section. To close the modal programmatically, you can use the close method provided by the Modal component.

```vue
<template>
    <Modal v-slot="{ close }">
        <button type="button" @click="close">Close</button>
    </Modal>
</template>
```

Alternatively, you can use the ref attribute to get a reference to the modal component and call the close method on it.

```vue
<script setup>
import { ref } from 'vue';

const modalRef = ref(null);

function closeModal() {
    modalRef.value.close();
}
</script>

<template>
    <Modal ref="modalRef">
        <!-- ... -->
    </Modal>
</template>
```

# Event Bus
Sometimes you need to communicate from the modal to the parent page. You can use the emit slot prop function for this purpose. Just like emitting events in Vue, you can pass a name and a payload to the emit function. The parent page can listen to these events using the @ directive. Here's an example of emitting an event from the modal:

```vue
<template>
    <Modal #default="{ emit }">
        <button type="button" @click="emit('increaseBy', 1)">
            Increase by 1
        </button>
    </Modal>
</template>
```

Alternatively, you can use the ref attribute to get a reference to the modal component and call the emit method on it.

```vue
<script setup>
import { ref } from 'vue';

const modalRef = ref(null);

function increaseBy(amount) {
    modalRef.value.emit('increaseBy', amount);
}
</script>

<template>
    <Modal ref="modalRef">
        <!-- ... -->
    </Modal>
</template>
```

On the parent page, you can listen to the event on the ModalLink component:

```vue
<template>
    <ModalLink href="/modal" @increase-by="handleIncrease">
        Open Modal
    </ModalLink>
</template>
```

If you're programmatically opening the modal, you add listeners using the listeners option:

```vue
visitModal('/users/create', {
    listeners: {
        increaseBy(amount) {
            console.log(`Increase by ${amount}`);
        }
    }
})
```

# Nested / Stacked Modals
Inertia Modal supports opening modals from within other modals. There's actually nothing special you need to do to make this work. Just use the ModalLink component inside the Modal component, and it will automatically open the new modal on top of the existing one:

```vue
<template>
    <Modal>
        <ModalLink href="/modal-2">
            Open Modal 2
        </ModalLink>
    </Modal>
</template>
```

# Communicating Between Modals
The Modal slot props contain ***getParentModal*** and ***getChildModal*** functions that allow you to grab the previous and next modals in the stack. Here is an example of triggering an event on the parent modal from the child modal:

```vue
<template>
    <Modal #default="{ getParentModal }">
        <button type="button" @click="getParentModal().emit('message', 'Hello from child')">
            Push message to parent
        </button>
    </Modal>
</template>
```

Alternatively, you can use the ref attribute to get a reference to the modal component and call the method on it.

```vue
<script setup>
import { ref } from 'vue';

const modalRef = ref(null);

function sendMessageToParent() {
    modalRef.value.getParentModal().emit('message', 'Hello from child');
}
</script>

<template>
    <Modal ref="modalRef">
        <!-- ... -->
    </Modal>
</template>
```

On the parent modal, you can listen to the event like a regular event listener:

```vue
<template>
    <Modal @message="handleMessage">
        <!-- ... -->
    </Modal>
</template>
```

# Listen for changes
Instead of using the emit method with custom event names, you may use one of these built-in events on the ModalLink component:

- **close**: Triggered when the modal is closed.
- **after-leave**: Triggered after the modal has been closed, the transition has ended, and it has been removed from the DOM.
- **blur**: Triggered when another modal is opened and the current modal is not the topmost modal.
- **focus**: Triggered when the opened child modal is closed and the current modal is focused again.

This can be useful for updating something in the parent modal when the child modal is closed.

```vue
<script setup>
function handleClosedChildModal() {
    console.log('Child modal closed');
}
</script>

<template>
    <!-- Parent Modal... -->
    <Modal>
        <ModalLink href="/modal-2" @close="handleClosedChildModal">
            Open Modal 2
        </ModalLink>
    </Modal>
</template>
```

Another great example is reloading the parent modal when the child modal is closed. This is described in the Reload Props documentation.

# Form Submissions in Nested Modals
When you have nested modals and need to submit forms, you'll need to be careful about how you handle the form submission. If you use Inertia's router (like router.post()), the redirect response will navigate back to the base route, which closes all modals in the stack.

Instead, use Axios for form submissions in nested modals:

```vue
<script setup>
import { default as Axios } from 'axios'
import { ref } from 'vue'

const modalRef = ref(null)

function submit() {
    Axios.post('/submit-form', formData).then(() => {
        modalRef.value.close() // Only closes the current modal
    })
}
</script>

<template>
    <Modal ref="modalRef">
        <form @submit.prevent="submit">
            <!-- Form fields -->
            <button type="submit">Submit</button>
        </form>
    </Modal>
</template>
```

This approach lets you manually control which modal closes, keeping the parent modal open while only closing the child modal that was submitted.

# Local Modals
Instead of opening a modal using a URL, you can also insert the modal content directly into the page. This is useful when you want to display a modal that is only relevant to a specific page and doesn't need to be shared across multiple pages. It's also great for simple modals that don't require a lot of logic.

To use a local modal, simply add the Modal component to your page and place the content directly inside the component. Next, you need to give the modal a unique name using the name prop.

To open the modal, you can use the ModalLink component and set the href prop to the name of the modal you want to open, prefixed with a # symbol.

```vue
<template>
    <!-- ... -->

    <ModalLink href="#confirm-action">
        Perform Action
    </ModalLink>

    <Modal name="confirm-action">
        <!-- ... -->
    </Modal>
</template>
```

## ✅ Modal Component Refactoring

Created Reusable Modal Components:

1. ModalRoot.vue - Container with proper height and flex layout (max-h-[80vh] flex flex-col)
2. ModalHeader.vue - Reusable header with icon, title, description, and close button
3. ModalScrollable.vue - Scrollable content area with custom scrollbar styling
4. ModalFooter.vue - Fixed footer with consistent button placement and styling

Benefits Achieved:

- 🔄 DRY Principle: Eliminated code duplication across modals
- 🎨 Consistency: All modals now have identical structure and styling
- 🛠️ Maintainability: Changes to modal behavior only need to be made in one place
- ♻️ Reusability: New modals can be created quickly using these components
- 📱 Responsive: Proper scrolling and layout on all screen sizes

How to Use the New Modal Structure:

```vue
  <template>
    <Modal v-slot="{ close }">
      <ModalRoot>
        <ModalHeader
          title="Modal Title"
          description="Optional description"
          :icon="SomeIcon"
          :on-close="close"
        />

        <ModalScrollable>
          <!-- Your modal content here -->
        </ModalScrollable>

        <ModalFooter>
          <!-- Your action buttons here -->
        </ModalFooter>
      </ModalRoot>
    </Modal>
  </template>
 ```

The modal system is now completely modularized and follows a consistent pattern across your entire application. Any
future modals can simply import these four components and follow the same structure.

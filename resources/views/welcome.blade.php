<x-layouts.app>

  <div x-data="{ mobileMenuOpen: false }">
    <!-- Navigation -->
    <nav class="fixed w-full z-50 bg-[#F5F5F3]/80 backdrop-blur-sm">
      <div class="max-w-7xl mx-auto px-6">
        <div class="flex justify-between h-20">
          <div class="flex items-center">
            <a href="/" class="text-2xl font-medium text-gray-900">
              BoardHub
            </a>
          </div>

          <!-- Desktop Navigation -->
          <div class="hidden sm:flex sm:items-center sm:space-x-8">
            <a href="#features" class="text-gray-600 hover:text-gray-900">Solutions</a>
            <a href="#services" class="text-gray-600 hover:text-gray-900">Services</a>
            <a href="#approach" class="text-gray-600 hover:text-gray-900">Our Approach</a>

            <div class="flex items-center space-x-4">
              @auth
                <a href="{{ url('/auth') }}" class="text-gray-600 hover:text-gray-900">Dashboard</a>
              @else
                <a href="{{ url('/auth/login') }}" class="inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-black hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-900">
                  Sign In
                </a>
              @endauth
            </div>
          </div>

          <!-- Mobile menu button -->
          <div class="flex items-center sm:hidden">
            <button @click="mobileMenuOpen = !mobileMenuOpen" class="text-gray-600 hover:text-gray-900">
              <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7" />
              </svg>
            </button>
          </div>
        </div>
      </div>
    </nav>

    <!-- Hero Section -->
    <div class="relative pt-32 pb-16 sm:pb-24">
      <div class="mx-auto max-w-7xl px-6">
        <div class="grid grid-cols-1 gap-16 lg:grid-cols-2 lg:gap-24">
          <div class="relative z-10">
            <p class="text-sm font-medium text-gray-600">{{ \App\Models\Billboard::count() }}+ Billboards Managed</p>
            <h1 class="mt-4 text-4xl font-medium tracking-tight text-gray-900 sm:text-6xl">
              Best Billboard Management Automation.
            </h1>
            <p class="mt-6 text-base text-gray-600">
              Automation is especially beneficial in managing outdoor advertising where manual tracking might lead to inefficiencies.
            </p>
            <div class="mt-8 flex items-center gap-x-6">
              @auth
                <a href="{{ url('/auth') }}" class="inline-flex items-center justify-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-black hover:bg-gray-800">
                  Go to Dashboard
                </a>
              @else
                <a href="{{ url('/auth/register') }}" class="inline-flex items-center justify-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-black hover:bg-gray-800">
                  Get Started
                </a>
              @endauth
              <a href="#features" class="text-base font-medium text-gray-600 hover:text-gray-900">
                Learn more <span aria-hidden="true">→</span>
              </a>
            </div>
          </div>
          <div class="relative">
            <div class="aspect-w-4 aspect-h-3 overflow-hidden rounded-2xl bg-[#BCE7FF]">
              <img src="https://images.unsplash.com/photo-1585537358121-7eb7d0e0c093?w=500&auto=format&fit=crop&q=60&ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxzZWFyY2h8MTl8fGJpbGxib2FyZCUyMG1hbmFnZW1lbnR8ZW58MHx8MHx8fDA%3D"
                   alt="Billboard Management"
                   class="object-cover">
              <div class="absolute bottom-4 right-4 bg-white/90 backdrop-blur-sm rounded-xl p-4 shadow-lg">
                <div class="flex items-center space-x-3">
                  <div class="h-8 w-8 rounded-full bg-gray-100"></div>
                  <div>
                    <p class="text-sm font-medium text-gray-900">Billboard Analytics</p>
                    <p class="text-xs text-gray-500">Real-time monitoring</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Logos Section -->
    <div class="mx-auto max-w-7xl px-6 py-12">
      <p class="text-center text-sm font-medium text-gray-600">
        Trusted by industry leaders
      </p>
      <div class="mt-8 grid grid-cols-2 gap-8 md:grid-cols-5">
        <div class="col-span-1 flex justify-center grayscale opacity-60 hover:opacity-100 transition">
          <img class="h-8" src="https://media.istockphoto.com/id/932680552/photo/businessman-building-advertising-concept-with-wooden-blocks.jpg?s=2048x2048&w=is&k=20&c=abeqDXxydKltFmepkAVV72lPHArt_ewBabFTKTt4CmE=" alt="Company 1">
        </div>
        <!-- Add more logos as needed -->
      </div>
    </div>

    <!-- Services Section -->
    <div id="services" class="mx-auto max-w-7xl px-6 py-24">
      <h2 class="text-3xl font-medium text-gray-900">Exclusive Services</h2>
      <div class="mt-12 grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-4">
        <div class="group relative rounded-2xl bg-white p-6 hover:shadow-lg transition">
          <div class="aspect-w-1 aspect-h-1 mb-6">
            <div class="h-12 w-12 rounded-full bg-amber-100 flex items-center justify-center">
              <svg class="h-6 w-6 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
            </div>
          </div>
          <h3 class="text-lg font-medium text-gray-900">Billboard Analytics</h3>
          <p class="mt-2 text-sm text-gray-600">Track performance and optimize your outdoor advertising campaigns.</p>
          <a href="#" class="mt-4 inline-flex items-center text-sm font-medium text-gray-600 hover:text-gray-900">
            Learn More →
          </a>
        </div>

        <!-- Locations Management -->
        <div class="group relative rounded-2xl bg-white p-6 hover:shadow-lg transition">
          <div class="aspect-w-1 aspect-h-1 mb-6">
            <div class="h-12 w-12 rounded-full bg-blue-100 flex items-center justify-center">
              <svg class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
              </svg>
            </div>
          </div>
          <h3 class="text-lg font-medium text-gray-900">Location Management</h3>
          <p class="mt-2 text-sm text-gray-600">Strategic billboard placement and location analytics for maximum impact.</p>
          <a href="#" class="mt-4 inline-flex items-center text-sm font-medium text-gray-600 hover:text-gray-900">
            Learn More →
          </a>
        </div>

        <!-- Contract Management -->
        <div class="group relative rounded-2xl bg-white p-6 hover:shadow-lg transition">
          <div class="aspect-w-1 aspect-h-1 mb-6">
            <div class="h-12 w-12 rounded-full bg-green-100 flex items-center justify-center">
              <svg class="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
            </div>
          </div>
          <h3 class="text-lg font-medium text-gray-900">Contract Management</h3>
          <p class="mt-2 text-sm text-gray-600">Streamline contract workflows with automated renewals and notifications.</p>
          <a href="#" class="mt-4 inline-flex items-center text-sm font-medium text-gray-600 hover:text-gray-900">
            Learn More →
          </a>
        </div>

        <!-- Financial Tracking -->
        <div class="group relative rounded-2xl bg-white p-6 hover:shadow-lg transition">
          <div class="aspect-w-1 aspect-h-1 mb-6">
            <div class="h-12 w-12 rounded-full bg-purple-100 flex items-center justify-center">
              <svg class="h-6 w-6 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
          </div>
          <h3 class="text-lg font-medium text-gray-900">Financial Tracking</h3>
          <p class="mt-2 text-sm text-gray-600">Monitor revenue, expenses, and generate detailed financial reports.</p>
          <a href="#" class="mt-4 inline-flex items-center text-sm font-medium text-gray-600 hover:text-gray-900">
            Learn More →
          </a>
        </div>
      </div>
    </div>

    <!-- Approach Section -->
    <div id="approach" class="mx-auto max-w-7xl px-6 py-24">
      <div class="grid grid-cols-1 gap-16 lg:grid-cols-2 lg:gap-24">
        <div class="relative">
          <div class="aspect-w-4 aspect-h-3 overflow-hidden rounded-2xl bg-[#FDE68A]">
            <img src="https://images.unsplash.com/photo-1745725427532-4c52cdc6d4ae?w=500&auto=format&fit=crop&q=60&ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxzZWFyY2h8MTR8fGJpbGxib2FyZCUyMG1hbmFnZW1lbnR8ZW58MHx8MHx8fDA%3D" alt="Our Approach" class="object-cover">
            <div class="absolute bottom-4 right-4 bg-white/90 backdrop-blur-sm rounded-xl p-4 shadow-lg">
              <div class="flex items-center space-x-3">
                <div class="h-16 w-16 rounded-lg bg-gray-100"></div>
                <div>
                  <p class="text-sm font-medium text-gray-900">Performance Reports</p>
                  <p class="text-xs text-gray-500">Updated in real-time</p>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div>
          <h2 class="text-3xl font-medium text-gray-900">Our unique Approach</h2>
          <p class="mt-6 text-base text-gray-600">
            We combine technology and expertise to deliver the best billboard management experience.
          </p>
          <div class="mt-12 space-y-8">
            <!-- Approach points -->
            <div class="flex items-start space-x-4">
              <div class="flex h-10 w-10 items-center justify-center rounded-full bg-gray-100">
                <span class="text-sm font-medium text-gray-900">01</span>
              </div>
              <div>
                <h3 class="text-lg font-medium text-gray-900">Automated Management</h3>
                <p class="mt-2 text-base text-gray-600">
                  Streamline operations with automated contract renewals and maintenance schedules.
                </p>
              </div>
            </div>

            <div class="flex items-start space-x-4">
              <div class="flex h-10 w-10 items-center justify-center rounded-full bg-gray-100">
                <span class="text-sm font-medium text-gray-900">02</span>
              </div>
              <div>
                <h3 class="text-lg font-medium text-gray-900">Data-Driven Decisions</h3>
                <p class="mt-2 text-base text-gray-600">
                  Make informed decisions with comprehensive analytics and performance metrics.
                </p>
              </div>
            </div>

            <div class="flex items-start space-x-4">
              <div class="flex h-10 w-10 items-center justify-center rounded-full bg-gray-100">
                <span class="text-sm font-medium text-gray-900">03</span>
              </div>
              <div>
                <h3 class="text-lg font-medium text-gray-900">Strategic Location Planning</h3>
                <p class="mt-2 text-base text-gray-600">
                  Optimize billboard placements using demographic and traffic data analysis.
                </p>
              </div>
            </div>

            <div class="flex items-start space-x-4">
              <div class="flex h-10 w-10 items-center justify-center rounded-full bg-gray-100">
                <span class="text-sm font-medium text-gray-900">04</span>
              </div>
              <div>
                <h3 class="text-lg font-medium text-gray-900">Simplified Compliance</h3>
                <p class="mt-2 text-base text-gray-600">
                  Stay compliant with automated permit tracking and renewal management.
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Stats Section -->
    <div class="bg-white py-24 sm:py-32">
      <div class="mx-auto max-w-7xl px-6">
        <div class="grid grid-cols-1 gap-8 lg:grid-cols-3">
          <div class="flex flex-col items-start">
            <h2 class="text-3xl font-medium text-gray-900">Our Impact in Numbers</h2>
            <p class="mt-4 text-base text-gray-600">Real results from our automated billboard management system.</p>
          </div>
          <div class="grid grid-cols-2 gap-8 sm:grid-cols-2 lg:col-span-2">
            <div>
              <p class="text-4xl font-medium text-gray-900">{{ number_format(\App\Models\Billboard::count()) }}</p>
              <p class="mt-2 text-base text-gray-600">Billboards Managed</p>
            </div>
            <div>
              <p class="text-4xl font-medium text-gray-900">{{ number_format(\App\Models\Location::count()) }}</p>
              <p class="mt-2 text-base text-gray-600">Strategic Locations</p>
            </div>
            <div>
              <p class="text-4xl font-medium text-gray-900">{{ number_format(\App\Models\Contract::where('agreement_status', 'active')->count()) }}</p>
              <p class="mt-2 text-base text-gray-600">Active Contracts</p>
            </div>
            <div>
              <p class="text-4xl font-medium text-gray-900">24/7</p>
              <p class="mt-2 text-base text-gray-600">Monitoring & Support</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Features Grid -->
    <div class="bg-gray-50 py-24 sm:py-32">
      <div class="mx-auto max-w-7xl px-6">
        <div class="mx-auto max-w-2xl lg:mx-0">
          <h2 class="text-3xl font-medium text-gray-900">Everything you need</h2>
          <p class="mt-6 text-base text-gray-600">
            Comprehensive tools and features to manage your billboard advertising business efficiently.
          </p>
        </div>
        <dl class="mx-auto mt-16 grid max-w-2xl grid-cols-1 gap-8 sm:mt-20 sm:grid-cols-2 lg:mx-0 lg:max-w-none lg:grid-cols-3">
          <div class="flex flex-col rounded-2xl bg-white p-6">
            <dt class="flex items-center gap-x-3 text-base font-medium text-gray-900">
              <svg class="h-5 w-5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
              </svg>
              Performance Analytics
            </dt>
            <dd class="mt-3 text-sm text-gray-600">Track impressions, engagement, and ROI for each billboard location.</dd>
          </div>
          <div class="flex flex-col rounded-2xl bg-white p-6">
            <dt class="flex items-center gap-x-3 text-base font-medium text-gray-900">
              <svg class="h-5 w-5 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              Automated Scheduling
            </dt>
            <dd class="mt-3 text-sm text-gray-600">Intelligent scheduling system for maintenance and content rotation.</dd>
          </div>
          <div class="flex flex-col rounded-2xl bg-white p-6">
            <dt class="flex items-center gap-x-3 text-base font-medium text-gray-900">
              <svg class="h-5 w-5 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
              </svg>
              Payment Processing
            </dt>
            <dd class="mt-3 text-sm text-gray-600">Secure payment processing and automated invoicing system.</dd>
          </div>
        </dl>
      </div>
    </div>

    <!-- CTA Section -->
    <div class="relative isolate overflow-hidden bg-gray-900">
      <div class="px-6 py-24 sm:px-6 sm:py-32 lg:px-8">
        <div class="mx-auto max-w-2xl text-center">
          <h2 class="text-3xl font-medium tracking-tight text-white sm:text-4xl">
            Ready to streamline your billboard management?
          </h2>
          <p class="mx-auto mt-6 max-w-xl text-lg leading-8 text-gray-300">
            Join the leading billboard management platform and transform your business today.
          </p>
          <div class="mt-10 flex items-center justify-center gap-x-6">
            @auth
              <a href="{{ url('/auth') }}" class="rounded-md bg-white px-3.5 py-2.5 text-sm font-semibold text-gray-900 shadow-sm hover:bg-gray-100 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white">
                Go to Dashboard
              </a>
            @else
              <a href="{{ url('/auth/login') }}" class="rounded-md bg-white px-3.5 py-2.5 text-sm font-semibold text-gray-900 shadow-sm hover:bg-gray-100 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white">
                Get Started
              </a>
              <a href="{{ url('/auth/login') }}" class="text-sm font-semibold leading-6 text-white">
                Log in <span aria-hidden="true">→</span>
              </a>
            @endauth
          </div>
        </div>
      </div>
      <svg viewBox="0 0 1024 1024" class="absolute left-1/2 top-1/2 -z-10 h-[64rem] w-[64rem] -translate-x-1/2 [mask-image:radial-gradient(closest-side,white,transparent)]" aria-hidden="true">
        <circle cx="512" cy="512" r="512" fill="url(#gradient)" fill-opacity="0.7" />
        <defs>
          <radialGradient id="gradient">
            <stop stop-color="#7775D6" />
            <stop offset="1" stop-color="#E935C1" />
          </radialGradient>
        </defs>
      </svg>
    </div>

    <!-- Footer -->
    <footer class="bg-white">
      <div class="mx-auto max-w-7xl px-6 py-12 md:flex md:items-center md:justify-between">
        <div class="mt-8 md:order-1 md:mt-0">
          <p class="text-center text-sm text-gray-600">
            &copy; {{ date('Y') }} BoardHub. All rights reserved.
          </p>
        </div>
      </div>
    </footer>
  </div>

</x-layouts.app>

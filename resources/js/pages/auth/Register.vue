<template>
  <div class="min-h-screen flex flex-col justify-center items-center bg-gradient-to-br from-gray-50 via-white to-gray-50 px-4 sm:px-6 lg:px-8">
    <div class="w-full max-w-md">
      <!-- Header -->
      <div class="text-center mb-10">
        <Link href="/" class="inline-block mb-6">
          <img src="/images/Tratz Academy-Horizontal Profile.svg" alt="Traitz Academy" class="h-10 w-auto mx-auto" />
        </Link>
        <h1 class="text-3xl font-bold text-[#000928] mb-2">Create Account</h1>
        <p class="text-gray-600">Join Traitz Academy and start your journey</p>
      </div>

      <!-- Register Form -->
      <form @submit.prevent="submit" class="bg-white rounded-2xl shadow-lg p-8 space-y-6">
        <!-- Name -->
        <div>
          <label for="name" class="block text-sm font-semibold text-gray-700 mb-2">Full Name</label>
          <input
            id="name"
            v-model="form.name"
            type="text"
            required
            autocomplete="name"
            placeholder="Your full name"
            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-[#42b6c5] focus:ring-2 focus:ring-[#42b6c5] focus:ring-opacity-20 transition-all duration-200"
            :class="{ 'border-red-500 focus:ring-red-500': form.errors.name }"
          />
          <p v-if="form.errors.name" class="mt-2 text-sm text-red-600">{{ form.errors.name }}</p>
        </div>

        <!-- Email -->
        <div>
          <label for="email" class="block text-sm font-semibold text-gray-700 mb-2">Email Address</label>
          <input
            id="email"
            v-model="form.email"
            type="email"
            required
            autocomplete="email"
            placeholder="your@email.com"
            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-[#42b6c5] focus:ring-2 focus:ring-[#42b6c5] focus:ring-opacity-20 transition-all duration-200"
            :class="{ 'border-red-500 focus:ring-red-500': form.errors.email }"
          />
          <p v-if="form.errors.email" class="mt-2 text-sm text-red-600">{{ form.errors.email }}</p>
        </div>

        <!-- Phone -->
        <div>
          <label for="phone" class="block text-sm font-semibold text-gray-700 mb-2">Phone / WhatsApp Number</label>
          <input
            id="phone"
            v-model="form.phone"
            type="tel"
            required
            autocomplete="tel"
            placeholder="+234xxxxxxxxxx"
            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-[#42b6c5] focus:ring-2 focus:ring-[#42b6c5] focus:ring-opacity-20 transition-all duration-200"
            :class="{ 'border-red-500 focus:ring-red-500': form.errors.phone }"
          />
          <p class="mt-1 text-xs text-gray-500">Include country code (e.g., +234 for Nigeria)</p>
          <p v-if="form.errors.phone" class="mt-1 text-sm text-red-600">{{ form.errors.phone }}</p>
        </div>

        <!-- Password -->
        <div>
          <label for="password" class="block text-sm font-semibold text-gray-700 mb-2">Password</label>
          <input
            id="password"
            v-model="form.password"
            type="password"
            required
            autocomplete="new-password"
            placeholder="••••••••"
            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-[#42b6c5] focus:ring-2 focus:ring-[#42b6c5] focus:ring-opacity-20 transition-all duration-200"
            :class="{ 'border-red-500 focus:ring-red-500': form.errors.password }"
          />
          <p v-if="form.errors.password" class="mt-2 text-sm text-red-600">{{ form.errors.password }}</p>
        </div>

        <!-- Confirm Password -->
        <div>
          <label for="password_confirmation" class="block text-sm font-semibold text-gray-700 mb-2">Confirm Password</label>
          <input
            id="password_confirmation"
            v-model="form.password_confirmation"
            type="password"
            required
            autocomplete="new-password"
            placeholder="••••••••"
            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-[#42b6c5] focus:ring-2 focus:ring-[#42b6c5] focus:ring-opacity-20 transition-all duration-200"
            :class="{ 'border-red-500 focus:ring-red-500': form.errors.password_confirmation }"
          />
          <p v-if="form.errors.password_confirmation" class="mt-2 text-sm text-red-600">{{ form.errors.password_confirmation }}</p>
        </div>

        <!-- Submit Button -->
        <button
          type="submit"
          :disabled="form.processing"
          class="w-full py-3 bg-[#42b6c5] text-white font-semibold rounded-lg hover:bg-[#35919e] disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200 transform hover:scale-105"
        >
          <span v-if="form.processing" class="inline-flex items-center justify-center">
            <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            Creating account...
          </span>
          <span v-else>Create Account</span>
        </button>

        <!-- Login Link -->
        <div class="text-center pt-4 border-t border-gray-200">
          <p class="text-sm text-gray-600">
            Already have an account?
            <Link href="/login" class="text-[#42b6c5] hover:text-[#35919e] font-semibold transition-colors">
              Sign in
            </Link>
          </p>
        </div>
      </form>

      <!-- Back to Home -->
      <div class="text-center mt-8">
        <Link href="/" class="text-sm text-gray-600 hover:text-gray-900 transition-colors">
          ← Back to Home
        </Link>
      </div>
    </div>
  </div>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3'
import { Link } from '@inertiajs/vue3'

const form = useForm({
  name: '',
  email: '',
  phone: '',
  password: '',
  password_confirmation: '',
})

const submit = () => {
  form.post('/register', {
    onFinish: () => {
      form.reset('password', 'password_confirmation')
    },
  })
}
</script>

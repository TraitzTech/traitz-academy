<script setup lang="ts">
import { Form, Head, Link, usePage } from '@inertiajs/vue3'
import { onUnmounted, ref } from 'vue'
import { ShieldBan, ShieldCheck } from 'lucide-vue-next'

import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController'
import PasswordController from '@/actions/App/Http/Controllers/Settings/PasswordController'
import HeadingSmall from '@/components/HeadingSmall.vue'
import InputError from '@/components/InputError.vue'
import TwoFactorRecoveryCodes from '@/components/TwoFactorRecoveryCodes.vue'
import TwoFactorSetupModal from '@/components/TwoFactorSetupModal.vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import AdminLayout from '@/layouts/AdminLayout.vue'
import { useTwoFactorAuth } from '@/composables/useTwoFactorAuth'
import { disable, enable } from '@/routes/two-factor'
import { send } from '@/routes/verification'

interface Props {
  mustVerifyEmail: boolean
  status?: string
  twoFactorEnabled: boolean
  requiresConfirmation: boolean
}

const props = defineProps<Props>()

defineOptions({
  layout: AdminLayout
})

const page = usePage()
const user = page.props.auth.user
const { hasSetupData, clearTwoFactorAuthData } = useTwoFactorAuth()
const showSetupModal = ref(false)

onUnmounted(() => {
  clearTwoFactorAuthData()
})
</script>

<template>
  <div>
    <Head title="Account Settings" />

    <div class="mb-8">
      <h2 class="text-3xl font-bold text-gray-900">Account Settings</h2>
      <p class="text-gray-600 mt-2">Manage your profile, password, and security preferences</p>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
      <!-- Profile Panel -->
      <section class="bg-white rounded-2xl shadow p-6 space-y-6">
        <HeadingSmall title="Profile information" description="Update your personal details" />

        <Form
          v-bind="ProfileController.update.form()"
          class="space-y-6"
          v-slot="{ errors, processing, recentlySuccessful }"
        >
          <div class="grid gap-2">
            <Label for="name">Name</Label>
            <Input id="name" class="mt-1 block w-full" name="name" :default-value="user.name" autocomplete="name" placeholder="Full name" />
            <InputError :message="errors.name" />
          </div>

          <div class="grid gap-2">
            <Label for="email">Email address</Label>
            <Input id="email" type="email" class="mt-1 block w-full" name="email" :default-value="user.email" autocomplete="username" placeholder="Email address" />
            <InputError :message="errors.email" />
          </div>

          <div v-if="mustVerifyEmail && !user.email_verified_at" class="text-sm text-gray-500">
            <span>Your email address is unverified.</span>
            <Link :href="send()" as="button" class="ml-2 text-[#42b6c5] font-semibold hover:underline">Resend verification email</Link>
            <p v-if="status === 'verification-link-sent'" class="mt-2 text-green-600">A new verification link has been sent to your email.</p>
          </div>

          <div class="flex items-center gap-4">
            <Button :disabled="processing" data-test="update-profile-button">Save changes</Button>
            <Transition enter-active-class="transition ease-in-out duration-200" enter-from-class="opacity-0" leave-active-class="transition ease-in-out duration-200" leave-to-class="opacity-0">
              <p v-show="recentlySuccessful" class="text-sm text-gray-500">Saved.</p>
            </Transition>
          </div>
        </Form>
      </section>

      <!-- Password Panel -->
      <section class="bg-white rounded-2xl shadow p-6 space-y-6">
        <HeadingSmall title="Password" description="Keep your account secure with a strong password" />

        <Form
          v-bind="PasswordController.update.form()"
          :options="{ preserveScroll: true }"
          reset-on-success
          :reset-on-error="['password', 'password_confirmation', 'current_password']"
          class="space-y-6"
          v-slot="{ errors, processing, recentlySuccessful }"
        >
          <div class="grid gap-2">
            <Label for="current_password">Current password</Label>
            <Input id="current_password" name="current_password" type="password" autocomplete="current-password" placeholder="Current password" />
            <InputError :message="errors.current_password" />
          </div>

          <div class="grid gap-2">
            <Label for="password">New password</Label>
            <Input id="password" name="password" type="password" autocomplete="new-password" placeholder="New password" />
            <InputError :message="errors.password" />
          </div>

          <div class="grid gap-2">
            <Label for="password_confirmation">Confirm password</Label>
            <Input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" placeholder="Confirm password" />
            <InputError :message="errors.password_confirmation" />
          </div>

          <div class="flex items-center gap-4">
            <Button :disabled="processing">Save password</Button>
            <Transition enter-active-class="transition ease-in-out duration-200" enter-from-class="opacity-0" leave-active-class="transition ease-in-out duration-200" leave-to-class="opacity-0">
              <p v-show="recentlySuccessful" class="text-sm text-gray-500">Saved.</p>
            </Transition>
          </div>
        </Form>
      </section>

      <!-- Two Factor Panel -->
      <section class="bg-white rounded-2xl shadow p-6 space-y-6 xl:col-span-2">
        <HeadingSmall title="Two-Factor Authentication" description="Add an extra layer of security to your admin account" />

        <div>
          <Badge :variant="twoFactorEnabled ? 'default' : 'destructive'">
            {{ twoFactorEnabled ? 'Enabled' : 'Disabled' }}
          </Badge>
          <p class="text-gray-600 mt-3">Protect your account with a secure one-time code from a TOTP app every time you sign in.</p>
        </div>

        <div v-if="!twoFactorEnabled" class="flex flex-col gap-4">
          <p class="text-sm text-gray-500">Enable 2FA to require a verification code whenever you sign in.</p>
          <div class="flex flex-col sm:flex-row gap-4">
            <Button v-if="hasSetupData" @click="showSetupModal = true" class="flex items-center gap-2">
              <ShieldCheck class="w-4 h-4" /> Continue setup
            </Button>
            <Form v-else v-bind="enable.form()" @success="showSetupModal = true" v-slot="{ processing }">
              <Button type="submit" :disabled="processing" class="flex items-center gap-2">
                <ShieldCheck class="w-4 h-4" /> Enable 2FA
              </Button>
            </Form>
          </div>
        </div>

        <div v-else class="space-y-4">
          <TwoFactorRecoveryCodes />
          <div class="flex items-center gap-4">
            <Form v-bind="disable.form()" v-slot="{ processing }">
              <Button variant="destructive" type="submit" :disabled="processing" class="flex items-center gap-2">
                <ShieldBan class="w-4 h-4" /> Disable 2FA
              </Button>
            </Form>
          </div>
        </div>

        <TwoFactorSetupModal v-model:isOpen="showSetupModal" :requiresConfirmation="props.requiresConfirmation" :twoFactorEnabled="props.twoFactorEnabled" />
      </section>
    </div>
  </div>
</template>

import { ref, computed } from 'vue'
import { router, usePage } from '@inertiajs/vue3'

export interface CartItem {
    cart_key: string
    swag: {
        id: number
        name: string
        slug: string
        category: string
        price: number
        image: string | null
        stock_quantity: number
        currency: string
    }
    variation: string | null
    quantity: number
}

export function useCart() {
    const updating = ref(false)

    const addToCart = (swagId: number, variation: string | null, quantity: number = 1) => {
        updating.value = true
        router.post('/ai-forge/cart/add', {
            swag_id: swagId,
            variation,
            quantity,
        }, {
            preserveScroll: true,
            onFinish: () => { updating.value = false },
        })
    }

    const updateQuantity = (cartKey: string, quantity: number) => {
        updating.value = true
        router.put('/ai-forge/cart/update', {
            cart_key: cartKey,
            quantity,
        }, {
            preserveScroll: true,
            onFinish: () => { updating.value = false },
        })
    }

    const removeItem = (cartKey: string) => {
        updating.value = true
        router.delete('/ai-forge/cart/remove', {
            data: { cart_key: cartKey },
            preserveScroll: true,
            onFinish: () => { updating.value = false },
        })
    }

    const formatMoney = (amount: number, currency: string = 'XAF') => {
        return new Intl.NumberFormat('en-CM', {
            style: 'currency',
            currency,
            minimumFractionDigits: 0,
            maximumFractionDigits: 0,
        }).format(amount)
    }

    return {
        updating,
        addToCart,
        updateQuantity,
        removeItem,
        formatMoney,
    }
}

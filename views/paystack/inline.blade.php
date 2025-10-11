<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Processing Payment</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- TailwindCSS + Vue + Paystack -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/vue@3/dist/vue.global.js"></script>
    <script src="https://js.paystack.co/v1/inline.js"></script>

    <!-- Tailwind Config (optional custom theme) -->
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        border: "hsl(214.3 31.8% 91.4%)",
                        input: "hsl(214.3 31.8% 91.4%)",
                        ring: "hsl(222.2 84% 4.9%)",
                        background: "hsl(0 0% 100%)",
                        foreground: "hsl(222.2 84% 4.9%)",
                        primary: {
                            DEFAULT: "hsl(222.2 47.4% 11.2%)",
                            foreground: "hsl(210 40% 98%)",
                        },
                        secondary: {
                            DEFAULT: "hsl(210 40% 96%)",
                            foreground: "hsl(222.2 84% 4.9%)",
                        },
                        muted: {
                            DEFAULT: "hsl(210 40% 96%)",
                            foreground: "hsl(215.4 16.3% 46.9%)",
                        },
                        card: {
                            DEFAULT: "hsl(0 0% 100%)",
                            foreground: "hsl(222.2 84% 4.9%)",
                        },
                    },
                    borderRadius: {
                        lg: "0.5rem",
                        md: "calc(0.5rem - 2px)",
                        sm: "calc(0.5rem - 4px)",
                    },
                }
            }
        };
    </script>
</head>
<body class="bg-background text-foreground min-h-screen flex items-center justify-center p-4">
    <div id="app" class="w-full max-w-md">
        <div class="bg-card border border-border rounded-lg shadow-sm p-6 text-center">
            <div class="space-y-2 mb-6">
                <h1 class="text-2xl font-semibold tracking-tight">Processing Payment</h1>
                <p class="text-sm text-muted-foreground">Please do not close this window...</p>
            </div>

            <div v-if="loading">
                <svg class="animate-spin h-6 w-6 mx-auto text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.4 0 0 5.4 0 12h4zm2 5.3A7.96 7.96 0 014 12H0c0 3 1.1 5.8 3 7.9l3-2.6z"></path>
                </svg>
                <p class="text-sm mt-2">Launching Paystack checkout...</p>
            </div>

            <div v-else class="mt-4 text-sm" :class="messageType === 'success' ? 'text-green-600' : 'text-red-600'">
                @{{ message }}
            </div>
        </div>
    </div>

    <script>
        const { createApp } = Vue;

        createApp({
            data() {
                return {
                    loading: true,
                    message: '',
                    messageType: 'success'
                };
            },
            mounted() {
                this.initiatePayment();
            },
            methods: {
                initiatePayment() {
                    const handler = PaystackPop.setup({
                        key: '{{ $publicKey }}',
                        email: '{{ $email }}',
                        amount: {{ intval($amount) * 100 }},
                        currency: 'NGN',
                        ref: 'ref_' + Math.floor((Math.random() * 1000000000) + 1),
                        metadata: {
                            custom_fields: [
                                {
                                    display_name: "Full Name",
                                    variable_name: "full_name",
                                    value: "{{ $firstName ?? '' }} {{ $lastName ?? '' }}"
                                },
                                {
                                    display_name: "Phone Number",
                                    variable_name: "phone_number",
                                    value: "{{ $phone ?? '' }}"
                                }
                            ]
                        },
                        callback: (response) => {
                            this.loading = false;
                            this.message = `Payment successful! Reference: ${response.reference}`;
                            this.messageType = 'success';

                            const callbackUrl = '{{ $callbackUrl }}';
                            if (callbackUrl) {
                                window.location.href = callbackUrl + '?reference=' + encodeURIComponent(response.reference);
                            }
                        },
                        onClose: () => {
                            this.loading = false;
                            this.message = 'Payment was cancelled by user.';
                            this.messageType = 'error';
                        }
                    });

                    handler.openIframe();
                }
            }
        }).mount('#app');
    </script>
</body>
</html>

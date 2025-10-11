<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Form</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/vue@3/dist/vue.global.js"></script>
    <script src="https://js.paystack.co/v1/inline.js"></script>
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
        }
    </script>
</head>
<body class="bg-background text-foreground min-h-screen flex items-center justify-center p-4">
    <div id="app" class="w-full max-w-md">
        <div class="bg-card border border-border rounded-lg shadow-sm p-6">
            <div class="space-y-2 text-center mb-6">
                <h1 class="text-2xl font-semibold tracking-tight">Complete Payment</h1>
                <p class="text-sm text-muted-foreground">Enter your details to proceed with payment</p>
            </div>

            <form @submit.prevent="initiatePayment" class="space-y-4">
                <div class="space-y-2">
                    <label for="email" class="text-sm font-medium leading-none">Email</label>
                    <input 
                        id="email"
                        v-model="form.email"
                        type="email" 
                        required
                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                        placeholder="Enter your email"
                    />
                </div>

                <div class="space-y-2">
                    <label for="amount" class="text-sm font-medium leading-none">Amount (₦)</label>
                    <input 
                        id="amount"
                        v-model="form.amount"
                        type="number" 
                        required
                        min="100"
                        step="0.01"
                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                        placeholder="0.00"
                    />
                </div>

                <div class="space-y-2">
                    <label for="firstName" class="text-sm font-medium leading-none">First Name</label>
                    <input 
                        id="firstName"
                        v-model="form.firstName"
                        type="text" 
                        required
                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                        placeholder="Enter your first name"
                    />
                </div>

                <div class="space-y-2">
                    <label for="lastName" class="text-sm font-medium leading-none">Last Name</label>
                    <input 
                        id="lastName"
                        v-model="form.lastName"
                        type="text" 
                        required
                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                        placeholder="Enter your last name"
                    />
                </div>

                <div class="space-y-2">
                    <label for="phone" class="text-sm font-medium leading-none">Phone Number</label>
                    <input 
                        id="phone"
                        v-model="form.phone"
                        type="tel" 
                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                        placeholder="Enter your phone number"
                    />
                </div>

                <button 
                    type="submit"
                    :disabled="loading"
                    class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-primary text-primary-foreground hover:bg-primary/90 h-10 px-4 py-2 w-full"
                >
                    <span v-if="loading" class="mr-2">
                        <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </span>
                    @{{ loading ? 'Processing...' : 'Pay ₦' + (form.amount || '0') }}
                </button>
            </form>

            <div v-if="message" class="mt-4 p-3 rounded-md text-sm" :class="messageType === 'success' ? 'bg-green-50 text-green-800 border border-green-200' : 'bg-red-50 text-red-800 border border-red-200'">
                @{{ message }}
            </div>
        </div>
    </div>

    <script>
        const { createApp } = Vue;

        createApp({
            data() {
                return {
                    form: {
                        email: '',
                        amount: '',
                        firstName: '',
                        lastName: '',
                        phone: ''
                    },
                    loading: false,
                    message: '',
                    messageType: 'success'
                }
            },
            methods: {
                initiatePayment() {
                    if (!this.validateForm()) return;

                    this.loading = true;
                    this.message = ''; 

                    const handler = PaystackPop.setup({
                        key: '{{ $publicKey }}', // Replace with your Paystack public key
                        email: this.form.email,
                        amount: this.form.amount * 100, // Amount in kobo
                        currency: 'NGN',
                        ref: 'ref_' + Math.floor((Math.random() * 1000000000) + 1),
                        metadata: {
                            custom_fields: [
                                {
                                    display_name: "Full Name",
                                    variable_name: "full_name",
                                    value: `${this.form.firstName} ${this.form.lastName}`
                                },
                                {
                                    display_name: "Phone Number",
                                    variable_name: "phone_number",
                                    value: this.form.phone
                                }
                            ]
                        },
                        callback: (response) => {
                            this.loading = false;
                            this.message = `Payment successful! Reference: ${response.reference}`;
                            this.messageType = 'success';
                            console.log('Payment successful:', response);
                            // You can send the response to your server for verification
                        },
                        onClose: () => {
                            this.loading = false;
                            this.message = 'Payment was cancelled';
                            this.messageType = 'error';
                        }
                    });

                    handler.openIframe();
                },
                validateForm() {
                    if (!this.form.email || !this.form.amount || !this.form.firstName || !this.form.lastName) {
                        this.message = 'Please fill in all required fields';
                        this.messageType = 'error';
                        return false;
                    }
                    
                    if (this.form.amount < 100) {
                        this.message = 'Minimum amount is ₦100';
                        this.messageType = 'error';
                        return false;
                    }

                    return true;
                }
            }
        }).mount('#app');
    </script>
</body>
</html>
<?php

declare(strict_types=1);

if (! class_exists('WP_Error')) {
    class WP_Error
    {
        /** @var array<string, mixed> */
        private array $data;

        public function __construct(
            private string $code = '',
            private string $message = '',
            array $data = [],
        ) {
            $this->data = $data;
        }

        public function get_error_code(): string
        {
            return $this->code;
        }

        public function get_error_message(): string
        {
            return $this->message;
        }

        /** @return array<string, mixed> */
        public function get_error_data(): array
        {
            return $this->data;
        }
    }
}

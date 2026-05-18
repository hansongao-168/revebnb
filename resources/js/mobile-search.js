export function registerMobileSearch(Alpine) {
    Alpine.data('mobileSearch', (config = {}) => ({
        isOpen: false,
        destination: config.destination ?? '',
        checkIn: config.checkIn ?? '',
        checkOut: config.checkOut ?? '',
        adults: config.adults ?? 1,
        children: config.children ?? 0,
        infants: config.infants ?? 0,
        pets: config.pets ?? 0,
        limits: {
            adults: config.limits?.adults ?? 16,
            children: config.limits?.children ?? 16,
            infants: config.limits?.infants ?? 5,
            pets: config.limits?.pets ?? 5,
        },

        get guestCount() {
            return this.adults + this.children;
        },

        get guestSummary() {
            if (this.guestCount === 1 && this.infants === 0 && this.pets === 0) {
                return '1 位客人';
            }

            if (this.guestCount > 0 && this.infants === 0 && this.pets === 0) {
                return `${this.guestCount} 位客人`;
            }

            const parts = [];

            if (this.guestCount > 0) {
                parts.push(`${this.guestCount} 位客人`);
            }

            if (this.infants > 0) {
                parts.push(`${this.infants} 婴儿`);
            }

            if (this.pets > 0) {
                parts.push(`${this.pets} 宠物`);
            }

            return parts.length ? parts.join(' · ') : '添加客人';
        },

        get dateSummary() {
            if (this.checkIn && this.checkOut) {
                return `${this.formatDateLabel(this.checkIn)} – ${this.formatDateLabel(this.checkOut)}`;
            }

            if (this.checkIn) {
                return `${this.formatDateLabel(this.checkIn)} 入住`;
            }

            return '任意日期';
        },

        get locationSummary() {
            return this.destination.trim() !== '' ? this.destination.trim() : '搜索目的地';
        },

        open() {
            this.isOpen = true;
            document.body.classList.add('overflow-hidden');

            this.$nextTick(() => {
                document.getElementById('mobile-search-destination')?.focus();
            });
        },

        close() {
            this.isOpen = false;
            document.body.classList.remove('overflow-hidden');
        },

        increment(field) {
            const max = this.limits[field] ?? 16;

            if (this[field] >= max) {
                return;
            }

            this[field] += 1;
        },

        decrement(field) {
            const min = field === 'adults' ? 1 : 0;

            if (this[field] <= min) {
                return;
            }

            this[field] -= 1;
        },

        formatDateLabel(isoDate) {
            if (! isoDate) {
                return '';
            }

            const parts = isoDate.split('-').map(Number);

            if (parts.length !== 3) {
                return isoDate;
            }

            return `${parts[1]}月${parts[2]}日`;
        },

        init() {
            this._onKeydown = (event) => {
                if (event.key === 'Escape' && this.isOpen) {
                    this.close();
                }
            };

            window.addEventListener('keydown', this._onKeydown);
        },

        destroy() {
            window.removeEventListener('keydown', this._onKeydown);
            document.body.classList.remove('overflow-hidden');
        },
    }));
}

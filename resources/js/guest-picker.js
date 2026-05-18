export function registerGuestPicker(Alpine) {
    Alpine.data('guestPicker', (config = {}) => ({
        open: false,
        adults: config.adults ?? 1,
        children: config.children ?? 0,
        infants: config.infants ?? 0,
        pets: config.pets ?? 0,
        summaryStyle: config.summaryStyle ?? 'full',
        usePortal: config.usePortal ?? false,
        popoverStyle: {},
        limits: {
            adults: config.limits?.adults ?? 16,
            children: config.limits?.children ?? 16,
            infants: config.limits?.infants ?? 5,
            pets: config.limits?.pets ?? 5,
        },

        get summary() {
            const parts = [];

            if (this.adults > 0) {
                parts.push(`${this.adults} 成人`);
            }

            if (this.children > 0) {
                parts.push(`${this.children} 儿童`);
            }

            if (this.infants > 0) {
                parts.push(`${this.infants} 婴儿`);
            }

            if (this.pets > 0) {
                parts.push(`${this.pets} 宠物`);
            }

            return parts.length ? parts.join('，') : '添加客人';
        },

        get displaySummary() {
            if (this.summaryStyle !== 'compact') {
                return this.summary;
            }

            const guestCount = this.adults + this.children;

            if (guestCount === 1 && this.infants === 0 && this.pets === 0) {
                return '1 位客人';
            }

            if (guestCount > 0 && this.infants === 0 && this.pets === 0) {
                return `${guestCount} 位客人`;
            }

            const parts = [];

            if (guestCount > 0) {
                parts.push(`${guestCount} 位客人`);
            }

            if (this.infants > 0) {
                parts.push(`${this.infants} 婴儿`);
            }

            if (this.pets > 0) {
                parts.push(`${this.pets} 宠物`);
            }

            return parts.length ? parts.join(' · ') : '添加客人';
        },

        toggle() {
            if (this.open) {
                this.close();

                return;
            }

            this.open = true;

            if (this.usePortal) {
                this.positionPopover();
            }
        },

        close() {
            this.open = false;
        },

        positionPopover() {
            this.$nextTick(() => {
                const trigger = this.$refs.trigger;

                if (! trigger) {
                    return;
                }

                const rect = trigger.getBoundingClientRect();
                const panelWidth = 400;
                const margin = 16;
                let left = rect.left + rect.width / 2 - panelWidth / 2;

                if (left + panelWidth > window.innerWidth - margin) {
                    left = window.innerWidth - panelWidth - margin;
                }

                left = Math.max(margin, left);

                const panelHeight = 340;
                const gap = 12;
                let top = rect.bottom + gap;

                if (top + panelHeight > window.innerHeight - margin) {
                    top = Math.max(margin, rect.top - panelHeight - gap);
                }

                this.popoverStyle = {
                    position: 'fixed',
                    top: `${top}px`,
                    left: `${left}px`,
                    width: `${panelWidth}px`,
                };
            });
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

        init() {
            if (! this.usePortal) {
                return;
            }

            const reposition = () => {
                if (this.open) {
                    this.positionPopover();
                }
            };

            window.addEventListener('resize', reposition);
            window.addEventListener('scroll', reposition, true);

            this.$watch('open', (value) => {
                if (value) {
                    reposition();
                }
            });
        },
    }));
}

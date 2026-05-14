/** @param {any} Alpine */
export function registerStayBookingData(Alpine) {
    Alpine.data('revebnbStayBooking', (config) => ({
        slug: config.slug,
        nightlyPrice: Number(config.nightlyPrice),
        minNights: Number(config.minNights),
        maxGuests: Number(config.maxGuests) || 10,
        month: config.initialMonth,
        checkIn: config.initialCheckIn,
        checkOut: config.initialCheckOut,
        /** @type {Set<string>} */
        blocked: new Set(),
        loading: false,
        cells: [],
        todayStr: '',

        init() {
            this.todayStr = this.toYmd(new Date());
            this.$watch('month', () => {
                this.fetchBlocked();
            });
            this.$watch('checkIn', () => {
                this.buildCells();
            });
            this.$watch('checkOut', () => {
                this.buildCells();
            });
            this.fetchBlocked();
        },

        monthLabel() {
            const [y, m] = this.month.split('-').map(Number);

            return `${y} 年 ${String(m).padStart(2, '0')} 月`;
        },

        prevMonth() {
            this.shiftMonth(-1);
        },

        nextMonth() {
            this.shiftMonth(1);
        },

        shiftMonth(delta) {
            const [y, m] = this.month.split('-').map(Number);
            const d = new Date(y, m - 1 + delta, 1);
            this.month = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`;
        },

        async fetchBlocked() {
            this.loading = true;
            try {
                const res = await fetch(
                    `/stays/${encodeURIComponent(this.slug)}/availability?month=${encodeURIComponent(this.month)}`,
                    {
                        headers: {
                            Accept: 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    },
                );
                if (! res.ok) {
                    return;
                }
                const data = await res.json();
                this.blocked = new Set(data.blocked_nights ?? []);
            } finally {
                this.loading = false;
                this.buildCells();
            }
        },

        buildCells() {
            const [y, m] = this.month.split('-').map(Number);
            const first = new Date(y, m - 1, 1);
            const lastDay = new Date(y, m, 0).getDate();
            const leading = (first.getDay() + 6) % 7;
            const cells = [];
            for (let i = 0; i < 42; i++) {
                const dayNum = i - leading + 1;
                if (dayNum < 1 || dayNum > lastDay) {
                    cells.push({ empty: true });

                    continue;
                }
                const d = new Date(y, m - 1, dayNum);
                const dateStr = this.toYmd(d);
                const past = dateStr < this.todayStr;
                const blocked = this.blocked.has(dateStr);
                const inStayRange =
                    Boolean(this.checkIn && this.checkOut && dateStr >= this.checkIn && dateStr < this.checkOut);
                cells.push({
                    empty: false,
                    dateStr,
                    dayNum,
                    past,
                    blocked,
                    inStayRange,
                    isCheckIn: this.checkIn === dateStr,
                    isCheckOut: this.checkOut === dateStr,
                });
            }
            this.cells = cells;
        },

        selectDay(dateStr) {
            if (this.isPastDate(dateStr) || this.blocked.has(dateStr)) {
                return;
            }
            if (this.checkIn && this.checkOut) {
                this.checkIn = dateStr;
                this.checkOut = null;

                return;
            }
            if (! this.checkIn) {
                this.checkIn = dateStr;

                return;
            }
            if (! this.checkOut) {
                if (dateStr <= this.checkIn) {
                    this.checkIn = dateStr;

                    return;
                }
                this.checkOut = dateStr;
                if (this.nightsCount() < this.minNights || this.rangeHasBlocked()) {
                    this.checkOut = null;
                }
            }
        },

        isPastDate(dateStr) {
            return dateStr < this.todayStr;
        },

        nightsCount() {
            if (! this.checkIn || ! this.checkOut) {
                return 0;
            }

            return this.nightDiff(this.checkIn, this.checkOut);
        },

        roomSubtotal() {
            return this.nightsCount() * this.nightlyPrice;
        },

        nightDiff(a, b) {
            const t1 = new Date(`${a}T00:00:00`).getTime();
            const t2 = new Date(`${b}T00:00:00`).getTime();

            return Math.round((t2 - t1) / (24 * 3600 * 1000));
        },

        rangeHasBlocked() {
            if (! this.checkIn || ! this.checkOut) {
                return false;
            }
            let d = new Date(`${this.checkIn}T00:00:00`);
            const end = new Date(`${this.checkOut}T00:00:00`);
            while (d < end) {
                if (this.blocked.has(this.toYmd(d))) {
                    return true;
                }
                d.setDate(d.getDate() + 1);
            }

            return false;
        },

        toYmd(d) {
            return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
        },
    }));
}

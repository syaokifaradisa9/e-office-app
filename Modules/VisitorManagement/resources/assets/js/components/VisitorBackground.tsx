export default function VisitorBackground() {
    return (
        <div className="pointer-events-none absolute inset-0 overflow-hidden">
            {/* Floating Geometric Shapes */}
            <div className="animate-float absolute right-[5%] top-[10%] h-32 w-32 rotate-12 rounded-3xl border-2 border-emerald-400/20 dark:border-emerald-500/10"></div>
            <div className="animate-float-delayed absolute right-[15%] top-[30%] h-24 w-24 rounded-full border-2 border-teal-400/20 dark:border-teal-500/10"></div>
            <div className="animate-float absolute bottom-[20%] right-[10%] h-20 w-20 -rotate-12 rounded-2xl border-2 border-cyan-400/20 dark:border-cyan-500/10"></div>
            <div className="animate-float-delayed absolute bottom-[40%] right-[25%] h-28 w-28 rotate-45 rounded-3xl border-2 border-emerald-400/20 dark:border-emerald-500/10"></div>
            <div className="animate-float absolute right-[8%] top-[55%] h-16 w-16 rotate-6 rounded-xl border-2 border-blue-400/20 dark:border-blue-500/10"></div>
            <div className="animate-float-delayed absolute right-[30%] top-[70%] h-12 w-12 -rotate-45 rounded-lg border-2 border-teal-400/20 dark:border-teal-500/10"></div>

            {/* Left side shapes */}
            <div className="animate-float absolute left-[5%] top-[15%] h-20 w-20 rotate-45 rounded-2xl border-2 border-emerald-400/15 dark:border-emerald-500/10"></div>
            <div className="animate-float-delayed absolute left-[10%] bottom-[25%] h-16 w-16 -rotate-12 rounded-xl border-2 border-teal-400/15 dark:border-teal-500/10"></div>

            {/* Gradient Orbs */}
            <div className="absolute right-[5%] top-[5%] h-72 w-72 rounded-full bg-gradient-to-br from-emerald-500/25 to-teal-400/15 opacity-60 blur-3xl dark:from-emerald-500/15 dark:to-teal-500/10"></div>
            <div className="absolute right-[-5%] top-[25%] h-96 w-96 rounded-full bg-gradient-to-bl from-cyan-400/20 to-emerald-400/15 opacity-50 blur-3xl dark:from-cyan-600/15 dark:to-emerald-600/10"></div>
            <div className="absolute bottom-[10%] right-[10%] h-80 w-80 rounded-full bg-gradient-to-tr from-teal-400/15 to-blue-400/10 opacity-50 blur-3xl dark:from-teal-600/10 dark:to-blue-600/[0.08]"></div>
            <div className="absolute left-[5%] top-[30%] h-64 w-64 rounded-full bg-gradient-to-tl from-emerald-400/20 to-cyan-400/15 opacity-50 blur-3xl dark:from-emerald-600/[0.12] dark:to-cyan-600/10"></div>

            {/* Decorative Dots Pattern */}
            <div className="absolute right-[3%] top-[20%] grid grid-cols-3 gap-2">
                {[...Array(9)].map((_, i) => (
                    <div key={`dot-top-${i}`} className="h-2 w-2 rounded-full bg-emerald-400/20 dark:bg-emerald-500/10"></div>
                ))}
            </div>
            <div className="absolute bottom-[25%] left-[5%] grid grid-cols-3 gap-2">
                {[...Array(9)].map((_, i) => (
                    <div key={`dot-bottom-${i}`} className="h-2 w-2 rounded-full bg-teal-400/15 dark:bg-teal-500/10"></div>
                ))}
            </div>

            {/* Abstract Lines */}
            <svg className="absolute right-[2%] top-[45%] h-32 w-32 text-emerald-400/15 dark:text-emerald-500/10" viewBox="0 0 100 100">
                <path d="M10,50 Q30,20 50,50 T90,50" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" />
                <path d="M10,60 Q30,30 50,60 T90,60" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" />
            </svg>
            <svg className="absolute bottom-[15%] left-[8%] h-40 w-40 rotate-180 text-teal-400/10 dark:text-teal-500/5" viewBox="0 0 100 100">
                <circle cx="20" cy="20" r="8" fill="none" stroke="currentColor" strokeWidth="2" />
                <circle cx="50" cy="30" r="12" fill="none" stroke="currentColor" strokeWidth="2" />
                <circle cx="75" cy="15" r="6" fill="none" stroke="currentColor" strokeWidth="2" />
            </svg>

            {/* Additional decorative elements */}
            <div className="absolute right-[40%] top-[8%] h-1 w-20 rotate-45 rounded-full bg-gradient-to-r from-emerald-400/30 to-transparent dark:from-emerald-500/20"></div>
            <div className="absolute bottom-[8%] left-[35%] h-1 w-16 -rotate-12 rounded-full bg-gradient-to-r from-teal-400/30 to-transparent dark:from-teal-500/20"></div>
        </div>
    );
}

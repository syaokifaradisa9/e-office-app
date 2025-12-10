export default function AuthBackground() {
    return (
        <div className="pointer-events-none absolute inset-0 overflow-hidden">
            {/* Floating Geometric Shapes */}
            <div className="animate-float absolute left-[5%] top-[10%] h-32 w-32 rotate-12 rounded-3xl border-2 border-primary/20 dark:border-primary/10"></div>
            <div className="animate-float-delayed absolute right-[10%] top-[20%] h-24 w-24 rounded-full border-2 border-primary/20 dark:border-primary/10"></div>
            <div className="animate-float absolute bottom-[15%] left-[15%] h-20 w-20 -rotate-12 rounded-2xl border-2 border-purple-400/20 dark:border-purple-500/10"></div>
            <div className="animate-float-delayed absolute bottom-[25%] right-[8%] h-28 w-28 rotate-45 rounded-3xl border-2 border-pink-400/20 dark:border-pink-500/10"></div>
            <div className="animate-float absolute left-[8%] top-[50%] h-16 w-16 rotate-6 rounded-xl border-2 border-cyan-400/20 dark:border-cyan-500/10"></div>
            <div className="animate-float-delayed absolute right-[15%] top-[60%] h-12 w-12 -rotate-45 rounded-lg border-2 border-emerald-400/20 dark:border-emerald-500/10"></div>

            {/* Gradient Orbs */}
            <div className="absolute left-[20%] top-[5%] h-72 w-72 rounded-full bg-gradient-to-br from-primary/30 to-blue-400/20 opacity-60 blur-3xl dark:from-primary/15 dark:to-blue-500/10"></div>
            <div className="absolute right-[15%] top-[10%] h-80 w-80 rounded-full bg-gradient-to-bl from-purple-400/25 to-pink-400/20 opacity-50 blur-3xl dark:from-purple-600/15 dark:to-pink-600/10"></div>
            <div className="absolute bottom-[10%] left-[25%] h-96 w-96 rounded-full bg-gradient-to-tr from-cyan-400/20 to-blue-400/15 opacity-50 blur-3xl dark:from-cyan-600/10 dark:to-blue-600/[0.08]"></div>
            <div className="absolute bottom-[5%] right-[20%] h-64 w-64 rounded-full bg-gradient-to-tl from-cyan-400/25 to-blue-400/20 opacity-60 blur-3xl dark:from-cyan-600/[0.12] dark:to-blue-600/10"></div>

            {/* Decorative Dots Pattern */}
            <div className="absolute left-[3%] top-[30%] grid grid-cols-3 gap-2">
                {[...Array(9)].map((_, i) => (
                    <div key={`dot-left-${i}`} className="h-2 w-2 rounded-full bg-primary/15 dark:bg-primary/10"></div>
                ))}
            </div>
            <div className="absolute bottom-[35%] right-[5%] grid grid-cols-3 gap-2">
                {[...Array(9)].map((_, i) => (
                    <div key={`dot-right-${i}`} className="h-2 w-2 rounded-full bg-blue-400/15 dark:bg-blue-500/10"></div>
                ))}
            </div>

            {/* Abstract Lines */}
            <svg className="absolute left-[2%] top-[40%] h-32 w-32 text-primary/10 dark:text-primary/5" viewBox="0 0 100 100">
                <path d="M10,50 Q30,20 50,50 T90,50" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" />
                <path d="M10,60 Q30,30 50,60 T90,60" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" />
            </svg>
            <svg className="absolute bottom-[20%] right-[3%] h-40 w-40 rotate-180 text-purple-400/10 dark:text-purple-500/5" viewBox="0 0 100 100">
                <circle cx="20" cy="20" r="8" fill="none" stroke="currentColor" strokeWidth="2" />
                <circle cx="50" cy="30" r="12" fill="none" stroke="currentColor" strokeWidth="2" />
                <circle cx="75" cy="15" r="6" fill="none" stroke="currentColor" strokeWidth="2" />
            </svg>
        </div>
    );
}

import {
  Archive, Award, BarChart3, Book, BookOpen, Bot, BrainCircuit, Briefcase,
  Camera, Clapperboard, ClipboardList, Cloud, Code2, Coins, CreditCard,
  Cpu, DollarSign, Eye, FlaskConical, GraduationCap, Globe,
  Handshake, Image as ImageIcon, KeyRound, Landmark, Lightbulb, LineChart,
  Lock, Medal, Megaphone, Monitor, Notebook, Package, Paintbrush, Palette,
  PenTool, PiggyBank, Presentation, Rocket, Ruler, ShieldCheck,
  Sigma, Siren, Smartphone, Sparkles, Star, Target, TriangleAlert,
  Trophy, Users, Volume2, Wallet, Wrench, Zap,
} from 'lucide-vue-next'
import type { Component } from 'vue'

/**
 * Curated icon set for course categories. Categories store a short `icon` key
 * (e.g. "code", "palette") rather than a raw emoji, so the icon renders
 * consistently across OS/browsers and matches the lucide icon language used
 * everywhere else in the app. Shared between the admin category manager and
 * every public/student page that displays a category.
 */
export const CATEGORY_ICON_GROUPS: { label: string; icons: { key: string; component: Component }[] }[] = [
  {
    label: 'Technology',
    icons: [
      { key: 'code', component: Code2 },
      { key: 'monitor', component: Monitor },
      { key: 'smartphone', component: Smartphone },
      { key: 'cpu', component: Cpu },
      { key: 'database', component: Archive },
      { key: 'globe', component: Globe },
    ],
  },
  {
    label: 'Data & AI',
    icons: [
      { key: 'bot', component: Bot },
      { key: 'brain', component: BrainCircuit },
      { key: 'bar-chart', component: BarChart3 },
      { key: 'line-chart', component: LineChart },
      { key: 'sigma', component: Sigma },
      { key: 'flask', component: FlaskConical },
    ],
  },
  {
    label: 'Design',
    icons: [
      { key: 'palette', component: Palette },
      { key: 'pen-tool', component: PenTool },
      { key: 'paintbrush', component: Paintbrush },
      { key: 'ruler', component: Ruler },
      { key: 'image', component: ImageIcon },
      { key: 'clapperboard', component: Clapperboard },
    ],
  },
  {
    label: 'Business',
    icons: [
      { key: 'briefcase', component: Briefcase },
      { key: 'clipboard', component: ClipboardList },
      { key: 'handshake', component: Handshake },
      { key: 'trophy', component: Trophy },
      { key: 'lightbulb', component: Lightbulb },
      { key: 'target', component: Target },
    ],
  },
  {
    label: 'Finance',
    icons: [
      { key: 'wallet', component: Wallet },
      { key: 'dollar', component: DollarSign },
      { key: 'credit-card', component: CreditCard },
      { key: 'landmark', component: Landmark },
      { key: 'coins', component: Coins },
      { key: 'piggy-bank', component: PiggyBank },
    ],
  },
  {
    label: 'Security',
    icons: [
      { key: 'shield', component: ShieldCheck },
      { key: 'lock', component: Lock },
      { key: 'key', component: KeyRound },
      { key: 'eye', component: Eye },
      { key: 'siren', component: Siren },
      { key: 'alert', component: TriangleAlert },
    ],
  },
  {
    label: 'Cloud & DevOps',
    icons: [
      { key: 'cloud', component: Cloud },
      { key: 'rocket', component: Rocket },
      { key: 'wrench', component: Wrench },
      { key: 'package', component: Package },
      { key: 'zap', component: Zap },
    ],
  },
  {
    label: 'Marketing',
    icons: [
      { key: 'megaphone', component: Megaphone },
      { key: 'volume', component: Volume2 },
      { key: 'star', component: Star },
      { key: 'sparkles', component: Sparkles },
      { key: 'camera', component: Camera },
      { key: 'users', component: Users },
    ],
  },
  {
    label: 'Education',
    icons: [
      { key: 'graduation-cap', component: GraduationCap },
      { key: 'book-open', component: BookOpen },
      { key: 'book', component: Book },
      { key: 'presentation', component: Presentation },
      { key: 'notebook', component: Notebook },
      { key: 'medal', component: Medal },
      { key: 'award', component: Award },
    ],
  },
]

export const CATEGORY_ICON_MAP: Record<string, Component> = Object.fromEntries(
  CATEGORY_ICON_GROUPS.flatMap((g) => g.icons.map((i) => [i.key, i.component])),
)

export function categoryIconFor(key: string | null | undefined): Component | null {
  return key ? CATEGORY_ICON_MAP[key] ?? null : null
}

import 'package:chamba_app/presentation/view_models/session_view_model.dart';
import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

class SplashScreen extends StatefulWidget {
  const SplashScreen({super.key});

  @override
  State<SplashScreen> createState() => _SplashScreenState();
}

class _SplashScreenState extends State<SplashScreen> with SingleTickerProviderStateMixin {
  late final AnimationController _pulse = AnimationController(
    vsync: this,
    duration: const Duration(milliseconds: 1400),
  )..repeat(reverse: true);

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) => _bootstrap());
  }

  @override
  void dispose() {
    _pulse.dispose();
    super.dispose();
  }

  Future<void> _bootstrap() async {
    final session = context.read<SessionViewModel>();
    await session.restore();
    if (!mounted) return;
    if (session.canAccessHome) {
      context.go('/home');
    } else {
      context.go('/login');
    }
  }

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;

    return Scaffold(
      body: Container(
        width: double.infinity,
        height: double.infinity,
        decoration: BoxDecoration(
          gradient: LinearGradient(
            begin: Alignment.topLeft,
            end: Alignment.bottomRight,
            colors: [
              scheme.primary,
              Color.lerp(scheme.primary, scheme.tertiary, 0.5)!,
              scheme.surfaceContainerLow,
            ],
            stops: const [0.0, 0.42, 1.0],
          ),
        ),
        child: SafeArea(
          child: Column(
            children: [
              const Spacer(flex: 2),
              ScaleTransition(
                scale: Tween<double>(begin: 0.96, end: 1.04).animate(
                  CurvedAnimation(parent: _pulse, curve: Curves.easeInOut),
                ),
                child: Container(
                  padding: const EdgeInsets.all(22),
                  decoration: BoxDecoration(
                    color: scheme.onPrimary.withValues(alpha: 0.14),
                    shape: BoxShape.circle,
                    boxShadow: [
                      BoxShadow(
                        color: Colors.black.withValues(alpha: 0.12),
                        blurRadius: 28,
                        offset: const Offset(0, 14),
                      ),
                    ],
                  ),
                  child: Icon(Icons.handyman_rounded, size: 56, color: scheme.onPrimary),
                ),
              ),
              const SizedBox(height: 28),
              Text(
                'Chamba',
                style: Theme.of(context).textTheme.displaySmall?.copyWith(
                      color: scheme.onPrimary,
                      fontWeight: FontWeight.w900,
                      letterSpacing: -1,
                    ),
              ),
              const SizedBox(height: 8),
              Padding(
                padding: const EdgeInsets.symmetric(horizontal: 40),
                child: Text(
                  'Servicios locales al alcance de tu mano',
                  textAlign: TextAlign.center,
                  style: Theme.of(context).textTheme.titleMedium?.copyWith(
                        color: scheme.onPrimary.withValues(alpha: 0.9),
                        fontWeight: FontWeight.w500,
                      ),
                ),
              ),
              const Spacer(flex: 2),
              SizedBox(
                width: 36,
                height: 36,
                child: CircularProgressIndicator(
                  strokeWidth: 3,
                  color: scheme.onPrimary.withValues(alpha: 0.9),
                ),
              ),
              const SizedBox(height: 32),
            ],
          ),
        ),
      ),
    );
  }
}

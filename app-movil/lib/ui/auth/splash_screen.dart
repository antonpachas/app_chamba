import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';
import '../../providers/session_provider.dart';

class SplashScreen extends StatefulWidget {
  const SplashScreen({super.key});

  @override
  State<SplashScreen> createState() => _SplashScreenState();
}

class _SplashScreenState extends State<SplashScreen> {
  @override
  void initState() {
    super.initState();
    _init();
  }

  Future<void> _init() async {
    final session = context.read<SessionProvider>();
    await session.init();
    if (!mounted) return;
    if (session.isAuthenticated) {
      context.go('/home');
    } else {
      context.go('/login');
    }
  }

  @override
  Widget build(BuildContext context) {
    return const Scaffold(
      backgroundColor: Color(0xFF003874),
      body: Center(
        child: Column(mainAxisSize: MainAxisSize.min, children: [
          Icon(Icons.search_rounded, size: 72, color: Colors.white),
          SizedBox(height: 16),
          Text('Busca PE',
              style: TextStyle(fontSize: 32, fontWeight: FontWeight.w900,
                  color: Colors.white, letterSpacing: -0.5)),
          SizedBox(height: 8),
          Text('Encuentra negocios cerca de ti',
              style: TextStyle(fontSize: 15, color: Color(0xFFBFD7F5))),
          SizedBox(height: 48),
          CircularProgressIndicator(color: Colors.white, strokeWidth: 2),
        ]),
      ),
    );
  }
}

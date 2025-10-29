"""
Quick Test - Run all basic tests at once
"""
import subprocess
import sys

def run_test(name, command):
    """Run a test and display results"""
    print("\n" + "=" * 70)
    print(f"🧪 TEST: {name}")
    print("=" * 70)
    
    try:
        result = subprocess.run(
            command,
            shell=True,
            capture_output=True,
            text=True,
            timeout=30
        )
        
        print(result.stdout)
        if result.stderr:
            print("STDERR:", result.stderr)
        
        return result.returncode == 0
    except subprocess.TimeoutExpired:
        print("❌ Test timed out")
        return False
    except Exception as e:
        print(f"❌ Error: {e}")
        return False


def main():
    print("""
╔══════════════════════════════════════════════════════════════╗
║         SMART PARKING SYSTEM - QUICK TEST SUITE             ║
╚══════════════════════════════════════════════════════════════╝
""")
    
    tests = [
        ("Database Slot Check", "python check_slots.py"),
        ("Full Database Integration", "python test_database.py"),
    ]
    
    results = {}
    
    for name, command in tests:
        results[name] = run_test(name, command)
    
    # Summary
    print("\n" + "=" * 70)
    print("📊 TEST SUMMARY")
    print("=" * 70)
    
    for name, passed in results.items():
        status = "✅ PASSED" if passed else "❌ FAILED"
        print(f"{status}: {name}")
    
    total = len(results)
    passed = sum(1 for v in results.values() if v)
    
    print(f"\n Total: {passed}/{total} tests passed")
    
    if passed == total:
        print("\n🎉 All tests passed!")
    else:
        print("\n⚠️  Some tests failed. Check the output above.")
    
    print("\n💡 To start the API server, run:")
    print("   python backend/main.py")
    print("\n💡 Then visit http://127.0.0.1:8000/docs for interactive API testing")


if __name__ == "__main__":
    main()

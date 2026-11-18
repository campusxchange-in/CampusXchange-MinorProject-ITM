import { useState } from "react";
import Navbar from "../components/Navbar";
import Hero from "../components/Hero";
import Whycampus from "../components/Whycampus";
import Categories from "../components/Categories";
import FeaturedProduct from "../components/FeaturedProduct";
import HowItWork from "../components/HowItWork";
import Testimonial from "../components/Testimonial";
import Stats from "../components/Stats";
import Faq from "../components/Faq";
import Cta from "../components/Cta";
import Footer from "../components/Footer";
import LoginRequired from "../components/LoginRequired";
import { useAuth } from "../context/AuthContext";

function Home() {
  const [showAuthModal, setShowAuthModal] = useState(false);
  const { isAuthenticated, loading } = useAuth();

  const handleProtectedClick = () => {
    if (loading || !isAuthenticated) {
      setShowAuthModal(true);
      return false;
    }
    return true;
  };

  return (
    <>
      <Navbar onProtectedClick={handleProtectedClick} />
      <Hero />
      <Whycampus />
      <Categories onProtectedClick={handleProtectedClick} />
      <FeaturedProduct onProtectedClick={handleProtectedClick} />
      <HowItWork />
      <Testimonial />
      <Stats />
      <Faq />
      <Cta onProtectedClick={handleProtectedClick} />
      <Footer onProtectedClick={handleProtectedClick} />
      <LoginRequired isOpen={showAuthModal} onClose={() => setShowAuthModal(false)} />
    </>
  );
}

export default Home;

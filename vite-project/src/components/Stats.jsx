// ==============================
// src/components/Stats/Stats.jsx
// ==============================

import "./Stats.css";
import { useEffect, useRef, useState } from "react";
import {
  FaUserGraduate,
  FaBoxOpen,
  FaHandshake,
  FaUniversity,
} from "react-icons/fa";
import { useUserPublicStats } from "../hooks/userPublicStats";

// Counter Component
// Counts up from 0 to `end` once the card is visible. If the real number from
// the database arrives (or changes) later, it animates to the new value.
function Counter({ end = 0, duration = 1500 }) {
  const [count, setCount] = useState(0);
  const [visible, setVisible] = useState(false);
  const counterRef = useRef(null);

  useEffect(() => {
    const observer = new IntersectionObserver(
      ([entry]) => {
        if (entry.isIntersecting) {
          setVisible(true);
          observer.disconnect();
        }
      },
      { threshold: 0.4 }
    );

    if (counterRef.current) {
      observer.observe(counterRef.current);
    }

    return () => observer.disconnect();
  }, []);

  useEffect(() => {
    if (!visible) return undefined;

    if (!end || end <= 0) {
      setCount(0);
      return undefined;
    }

    let frame;
    const startTime = performance.now();

    const tick = (now) => {
      const progress = Math.min((now - startTime) / duration, 1);
      setCount(Math.floor(end * progress));
      if (progress < 1) {
        frame = requestAnimationFrame(tick);
      } else {
        setCount(end);
      }
    };

    frame = requestAnimationFrame(tick);
    return () => cancelAnimationFrame(frame);
  }, [visible, end, duration]);

  return <span ref={counterRef}>{count.toLocaleString("en-IN")}</span>;
}

export default function Stats() {
  const { stats } = useUserPublicStats();

  return (
    <section className="stats"data-aos="fade-up">

      <div className="stats-heading">
        <span>OUR IMPACT</span>

        <h2>
          Growing Every Day
          <br />
          Across Campuses
        </h2>

        <p>
          Students are joining CampusXchange every day to buy,
          sell and exchange products safely.
        </p>
      </div>

      <div className="stats-container">

        <div className="stat-card">
          <div className="stat-icon">
            <FaUserGraduate />
          </div>

          <h3>
            <Counter end={stats?.members ?? 0} />
          </h3>

          <p>Registered Users</p>
        </div>

        <div className="stat-card">
          <div className="stat-icon">
            <FaBoxOpen />
          </div>

          <h3>
            <Counter end={stats?.products_listed ?? 0} />
          </h3>

          <p>Products Listed</p>
        </div>

        <div className="stat-card">
          <div className="stat-icon">
            <FaHandshake />
          </div>

          <h3>
            <Counter end={stats?.deals ?? 0} />
          </h3>

          <p>Successful Deals</p>
        </div>

       {/* <div className="stat-card">
          <div className="stat-icon">
            <FaUniversity />
          </div>

          <h3>
            <Counter end={25} />+
          </h3>

          <p>Partner Colleges</p>
        </div>*/}

      </div>

    </section>
  );
}
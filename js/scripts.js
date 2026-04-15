'use strict';

/**
 * Landing Page Festival 14 de Maio - Garota SADE 2026
 * Funcionalidades:
 * 1) Menu mobile (hambúrguer)
 * 2) Scroll reveal das seções
 * 3) Simulação de votos por candidata (front-end)
 */

document.addEventListener('DOMContentLoaded', () => {
  setupMobileMenu();
  setupScrollReveal();
  setupVotingSystem();
});

function setupMobileMenu() {
  const menuToggle = document.getElementById('menuToggle');
  const menuPrincipal = document.getElementById('menuPrincipal');

  if (!menuToggle || !menuPrincipal) return;

  menuToggle.addEventListener('click', () => {
    const isActive = menuPrincipal.classList.toggle('active');
    menuToggle.setAttribute('aria-expanded', String(isActive));
  });

  // Fecha menu ao clicar em qualquer link (UX mobile)
  menuPrincipal.querySelectorAll('a').forEach((link) => {
    link.addEventListener('click', () => {
      menuPrincipal.classList.remove('active');
      menuToggle.setAttribute('aria-expanded', 'false');
    });
  });
}

function setupScrollReveal() {
  const revealElements = document.querySelectorAll('.section-reveal');

  if (!('IntersectionObserver' in window) || revealElements.length === 0) {
    revealElements.forEach((element) => element.classList.add('visible'));
    return;
  }

  const observer = new IntersectionObserver(
    (entries, currentObserver) => {
      entries.forEach((entry) => {
        if (!entry.isIntersecting) return;

        entry.target.classList.add('visible');
        currentObserver.unobserve(entry.target);
      });
    },
    {
      threshold: 0.15,
      rootMargin: '0px 0px -40px 0px'
    }
  );

  revealElements.forEach((element) => observer.observe(element));
}

function setupVotingSystem() {
  const voteButtons = document.querySelectorAll('.btn-vote');

  if (voteButtons.length === 0) return;

  const votes = initializeVotes(voteButtons);

  voteButtons.forEach((button) => {
    button.addEventListener('click', () => {
      const candidateId = button.dataset.id;
      if (!candidateId || !(candidateId in votes)) return;

      votes[candidateId] += 1;
      updateVoteUI(candidateId, votes[candidateId]);
      showVoteFeedback(candidateId);
      animateButton(button);
    });
  });
}

function initializeVotes(buttonList) {
  const voteData = {};

  buttonList.forEach((button) => {
    const id = button.dataset.id;
    if (!id) return;

    voteData[id] = 0;
    updateVoteUI(id, 0);
  });

  return voteData;
}

function updateVoteUI(candidateId, voteTotal) {
  const voteCounter = document.getElementById(`votes-${candidateId}`);
  if (voteCounter) voteCounter.textContent = String(voteTotal);
}

function showVoteFeedback(candidateId) {
  const feedback = document.getElementById(`feedback-${candidateId}`);
  if (!feedback) return;

  feedback.textContent = '✅ Voto computado com sucesso!';
  feedback.classList.add('show');

  window.clearTimeout(feedback.dataset.timeoutId);
  const timeoutId = window.setTimeout(() => {
    feedback.classList.remove('show');
    feedback.textContent = '';
  }, 1500);

  feedback.dataset.timeoutId = String(timeoutId);
}

function animateButton(button) {
  button.style.transform = 'scale(0.98)';
  window.setTimeout(() => {
    button.style.transform = '';
  }, 120);
}
